<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\RateLimitedException;

class GenerateProductEmbeddings extends Command
{
    protected $signature = 'products:generate-embeddings
                            {--force : Regenerate embeddings for products that already have one}
                            {--delay=2 : Seconds to wait between batches to avoid rate limits (0 to disable)}';

    protected $description = 'Generate and store OpenAI embeddings for products using text-embedding-3-small';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $delaySeconds = max(0, (int) $this->option('delay'));

        $query = Product::query()->with(['category', 'brand']);

        if (! $force) {
            $query->whereNull('embedding');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info(
                $force
                    ? 'No products to process.'
                    : 'All products already have embeddings. Use --force to regenerate.'
            );

            return self::SUCCESS;
        }

        $this->info("Generating embeddings for {$total} product(s).");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // 🔥 Smaller chunk size prevents token spikes
        $query->chunk(10, function ($products) use ($force, $delaySeconds, $bar) {

            $toProcess = $force
                ? $products
                : $products->filter(fn (Product $p) => $p->embedding === null);

            if ($toProcess->isEmpty()) {
                $bar->advance($products->count());
                return;
            }

            $texts = $toProcess
                ->map(fn (Product $p) => $p->toEmbeddingText())
                ->values()
                ->all();

            // 🔁 Retry with exponential backoff (rate-limit safe)
            $response = retry(
                3,
                function () use ($texts) {
                    return Embeddings::for($texts)
                        ->generate(Lab::OpenAI, 'text-embedding-3-small');
                },
                2000, // wait 2 seconds between retries
                function ($exception) {
                    return $exception instanceof RateLimitedException;
                }
            );

            foreach ($toProcess->values() as $i => $product) {
                $product->embedding = $response->embeddings[$i];
                $product->saveQuietly();
            }

            $bar->advance($products->count());

            // ⏳ Optional delay between batches
            if ($delaySeconds > 0) {
                sleep($delaySeconds);
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}