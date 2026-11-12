<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

#[Signature('ml:fetch-prototypes 
    {--limit=0 : Max number of species to process (0 = all)} 
    {--priority=philippine : Priority ordering (philippine, medicinal, targets, all)} 
    {--photos=3 : Reference photos per species} 
    {--species= : Comma-separated scientific names} 
    {--force : Re-fetch existing prototypes}')]
#[Description('Fetch research-grade iNaturalist reference photos and build visual prototypes for BioCLIP')]
class FetchPrototypesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $priority = (string) ($this->option('priority') ?? 'philippine');
        $photos = (int) ($this->option('photos') ?? 3);
        $species = (string) ($this->option('species') ?? '');
        $force = (bool) $this->option('force');

        $this->info("Fetching visual prototypes from iNaturalist (priority: {$priority}, photos/species: {$photos})...");

        $command = [
            'uv', 'run', 'python', 'scripts/fetch_prototypes.py',
            "--priority={$priority}",
            "--photos-per-species={$photos}",
        ];

        if ($limit > 0) {
            $command[] = "--limit={$limit}";
        }

        if ($species !== '') {
            $command[] = "--species={$species}";
        }

        if ($force) {
            $command[] = '--force';
        }

        $process = new Process($command, base_path('ml-service'));
        $process->setTimeout(3600);

        $process->run(function ($type, $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('Failed to fetch visual prototypes.');

            return self::FAILURE;
        }

        $this->info('Visual prototypes successfully updated.');

        // Attempt hot-reload if the ML service is active
        try {
            $response = Http::mlService()
                ->timeout(60)
                ->post('/v1/taxa/reload');

            if ($response->successful()) {
                $this->info('ML service successfully reloaded updated prototypes.');
            }
        } catch (ConnectionException) {
            // Service is not currently running; it will load on next start.
        }

        return self::SUCCESS;
    }
}
