<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Laravel\Ai\Embeddings; // <-- The correct class
use Laravel\Ai\Enums\Lab;  // <-- The correct enum
use Exception;

class GenerateProductEmbeddings extends Command
{
    protected $signature = 'products:generate-embeddings {--force : Regenerate embeddings} {--delay=2 : Seconds to wait}';
    protected $description = 'Generate and store OpenAI embeddings for products';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $delaySeconds = max(0, (int) $this->option('delay'));

        // MUST include orderBy() when using chunk()
        $query = Product::query()->with(['category', 'brand'])->orderBy('id');

        if (! $force) {
            $query->whereNull('embedding');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info($force ? 'No products to process.' : 'All products already have embeddings.');
            return self::SUCCESS;
        }

        $this->info("Generating embeddings for {$total} product(s).");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(10, function ($products) use ($force, $delaySeconds, $bar) {

            $toProcess = $force ? $products : $products->filter(fn (Product $p) => $p->embedding === null);

            if ($toProcess->isEmpty()) {
                $bar->advance($products->count());
                return;
            }

            // Pluck out an array of 10 text strings
            $texts = $toProcess->map(fn (Product $p) => $p->toEmbeddingText())->values()->all();

            try {
                // Retry 3 times, wait 2 seconds between fails
                $response = retry(3, function () use ($texts) {
                    
                    // THIS IS THE CORRECT LARAVEL 12 AI SDK SYNTAX
                    return Embeddings::for($texts)->generate(Lab::OpenAI, 'text-embedding-3-small');
                    
                }, 2000);

                foreach ($toProcess->values() as $i => $product) {
                    $product->embedding = $response->embeddings[$i];
                    $product->saveQuietly();
                }
            } catch (Exception $e) {
                $this->error("\nBatch failed: " . $e->getMessage());
            }

            $bar->advance($products->count());

            if ($delaySeconds > 0) {
                sleep($delaySeconds);
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Done. All embeddings synced!');

        return self::SUCCESS;
    }
}