<?php

namespace App\Jobs;

use App\Events\IdentificationUpdated;
use App\Models\Identification;
use App\Models\Plant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Sends a scan to the Python identification service and records the ranked candidates.
 * Inference is read-only on the service side, so retrying the request is safe.
 */
class RunIdentification implements ShouldQueue
{
    use Queueable;

    /**
     * Generous retries let a scan survive a hosted ML service waking from sleep.
     */
    public int $tries = 5;

    /**
     * Must stay below the queue connection's retry_after (90s).
     */
    public int $timeout = 80;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60, 120];

    public function __construct(public Identification $identification) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->identification->update(['status' => 'processing']);
        $this->broadcast();

        $result = Http::mlService()
            ->timeout(config('services.ml.timeout'))
            ->attach('image', Storage::disk(config('filesystems.media'))->get($this->identification->image_path), 'scan.jpg')
            ->post('/v1/identify', [
                'plant_part' => $this->identification->plant_part,
                'top_k' => 5,
            ])
            ->throw()
            ->json();

        $candidates = collect($result['candidates'] ?? []);
        $plants = Plant::whereIn('scientific_name', $candidates->pluck('scientific_name'))
            ->get(['id', 'scientific_name', 'common_name'])
            ->keyBy('scientific_name');

        $ranked = $candidates->map(fn (array $candidate) => [
            'plant_id' => $plants->get($candidate['scientific_name'])?->id,
            'scientific_name' => $candidate['scientific_name'],
            'common_name' => $plants->get($candidate['scientific_name'])?->common_name,
            'family' => $candidate['family'] ?? null,
            'score' => (float) $candidate['score'],
        ])->values();

        $top = $ranked->first();

        $this->identification->update([
            'status' => 'completed',
            'model_version' => $result['model_version'] ?? null,
            'candidates' => $ranked->all(),
            'top_plant_id' => $top['plant_id'] ?? null,
            'top_confidence' => $top['score'] ?? null,
            'needs_more_images' => ($top['score'] ?? 0) < config('services.ml.low_confidence_threshold'),
            'latency_ms' => $result['latency_ms'] ?? null,
            'error' => null,
        ]);

        $this->broadcast();
    }

    public function failed(?Throwable $exception): void
    {
        $this->identification->update([
            'status' => 'failed',
            'error' => 'The identification service is unavailable. Please try again or ask an expert.',
        ]);

        $this->broadcast();
    }

    /**
     * Live updates are best-effort: the page also polls, so a websocket outage must not fail the job.
     */
    private function broadcast(): void
    {
        rescue(fn () => IdentificationUpdated::dispatch($this->identification));
    }
}
