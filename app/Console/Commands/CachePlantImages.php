<?php

namespace App\Console\Commands;

use App\Actions\StoreSanitizedImage;
use App\Models\Plant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

/**
 * Downloads each plant's source image once and serves it from our own storage.
 * The source (pfaf.org) is slow and drops requests when a page asks for many images,
 * so it is fetched politely: a few at a time, with a pause, resumable on re-run.
 */
#[Signature('phytoquest:cache-plant-images {--limit= : Process at most this many plants} {--concurrency=4 : Parallel downloads} {--force : Re-download images already cached}')]
#[Description('Store plant images locally instead of loading them from the source site')]
class CachePlantImages extends Command
{
    public const MAX_DIMENSION = 900;

    /**
     * Execute the console command.
     */
    public function handle(StoreSanitizedImage $storeImage): int
    {
        $plants = Plant::query()
            ->whereNotNull('image_url')
            ->when(! $this->option('force'), fn ($query) => $query->whereNull('image_path'))
            ->orderBy('id')
            ->when($this->option('limit'), fn ($query, $limit) => $query->limit((int) $limit))
            ->get(['id', 'image_url', 'image_path']);

        if ($plants->isEmpty()) {
            $this->info('All plant images are already cached.');

            return self::SUCCESS;
        }

        $stored = $failed = 0;
        $bar = $this->output->createProgressBar($plants->count());

        foreach ($plants->chunk(max(1, (int) $this->option('concurrency'))) as $batch) {
            $responses = Http::pool(fn (Pool $pool) => $batch->map(
                fn (Plant $plant) => $pool->as((string) $plant->id)
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->retry(2, 2000, throw: false)
                    ->withHeaders(['User-Agent' => 'PhytoQuest research cache (+'.config('app.url').')'])
                    ->get($plant->image_url)
            )->all());

            foreach ($batch as $plant) {
                $response = $responses[(string) $plant->id] ?? null;

                try {
                    if (! $response instanceof Response || ! $response->successful()) {
                        throw new InvalidArgumentException('download failed');
                    }

                    $path = $storeImage->storeContents($response->body(), "plants/{$plant->id}.jpg", self::MAX_DIMENSION);
                    $plant->forceFill(['image_path' => $path])->save();
                    $stored++;
                } catch (Throwable) {
                    $failed++;
                }

                $bar->advance();
            }

            usleep(250_000);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Cached {$stored} images.".($failed ? " {$failed} could not be fetched; re-run the command to retry them." : ''));

        return self::SUCCESS;
    }
}
