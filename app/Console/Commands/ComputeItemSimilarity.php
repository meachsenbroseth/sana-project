<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeItemSimilarity extends Command
{
    protected $signature = 'recommendations:compute-similarity';

    protected $description = 'Compute pairwise item-item cosine similarity from user interactions';

    public function handle(): int
    {
        $lookbackDays = (int) config('recommendations.similarity_lookback_days', 90);
        $minScore = (float) config('recommendations.similarity_min_score', 0.1);

        $this->info("Computing item similarity (lookback: {$lookbackDays} days, min score: {$minScore})...");

        $rows = DB::select("
            WITH user_item_weights AS (
                SELECT
                    COALESCE(customer_id::text, session_id) AS actor,
                    product_id,
                    SUM(weight) AS total_weight
                FROM user_interactions
                WHERE created_at >= NOW() - INTERVAL '{$lookbackDays} days'
                  AND COALESCE(customer_id::text, session_id) IS NOT NULL
                GROUP BY actor, product_id
            ),
            pairwise AS (
                SELECT
                    a.product_id AS product_a,
                    b.product_id AS product_b,
                    SUM(a.total_weight * b.total_weight) AS dot_product
                FROM user_item_weights a
                INNER JOIN user_item_weights b
                    ON a.actor = b.actor
                    AND a.product_id < b.product_id
                GROUP BY a.product_id, b.product_id
            ),
            norms AS (
                SELECT
                    product_id,
                    SQRT(SUM(total_weight * total_weight)) AS norm
                FROM user_item_weights
                GROUP BY product_id
            )
            SELECT
                p.product_a,
                p.product_b,
                p.dot_product / (na.norm * nb.norm) AS score
            FROM pairwise p
            INNER JOIN norms na ON na.product_id = p.product_a
            INNER JOIN norms nb ON nb.product_id = p.product_b
            WHERE (p.dot_product / (na.norm * nb.norm)) > ?
        ", [$minScore]);

        $this->info('Found '.count($rows).' similar pairs.');

        DB::table('product_similarities')->truncate();

        $inserts = [];

        foreach ($rows as $row) {
            $inserts[] = [
                'product_id' => $row->product_a,
                'related_id' => $row->product_b,
                'score' => $row->score,
            ];
            $inserts[] = [
                'product_id' => $row->product_b,
                'related_id' => $row->product_a,
                'score' => $row->score,
            ];
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('product_similarities')->insert($chunk);
        }

        $this->info('Inserted '.count($inserts).' rows into product_similarities.');

        return self::SUCCESS;
    }
}
