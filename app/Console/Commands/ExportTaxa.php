<?php

namespace App\Console\Commands;

use App\Models\Plant;
use Database\Seeders\PlantSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Writes the catalogue's species list as the label set for the ML identification service,
 * then asks a running service to reload it.
 */
#[Signature('phytoquest:export-taxa {--path= : Output JSON path}')]
#[Description('Export catalogue taxa as labels for the ML identification service')]
class ExportTaxa extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->option('path') ?? config('services.ml.taxa_path');

        $pilotSpecies = collect(PlantSeeder::PILOT_SPECIES)
            ->map(fn (array $row) => strtolower($row[1]))
            ->flip();

        $taxa = Plant::query()
            ->orderBy('scientific_name')
            ->get(['scientific_name', 'family', 'common_name', 'common_names', 'category', 'native_range', 'distribution', 'medicinal_rating'])
            ->map(function (Plant $plant) use ($pilotSpecies) {
                $rawAliases = $plant->common_names
                    ? preg_split('/[,;\/]+/', (string) $plant->common_names)
                    : [];

                $aliases = collect($rawAliases)
                    ->map(fn ($name) => trim((string) $name))
                    ->filter(fn ($name) => $name !== '' && strcasecmp($name, (string) $plant->common_name) !== 0)
                    ->unique()
                    ->values()
                    ->all();

                $isPhilippine = $pilotSpecies->has(strtolower((string) $plant->scientific_name))
                    || str_contains(strtolower((string) $plant->native_range), 'philippin')
                    || str_contains(strtolower((string) $plant->distribution), 'philippin');

                return [
                    'scientific_name' => $plant->scientific_name,
                    'family' => $plant->family,
                    'common_name' => $plant->common_name,
                    'aliases' => $aliases,
                    'category' => $plant->category,
                    'is_philippine' => $isPhilippine,
                    'medicinal_rating' => $plant->medicinal_rating,
                ];
            });

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $taxa->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Wrote {$taxa->count()} taxa to {$path}.");

        try {
            $response = Http::mlService()
                ->timeout(600)
                ->post('/v1/taxa/reload');

            $response->successful()
                ? $this->info('ML service reloaded '.$response->json('taxa_count').' taxa.')
                : $this->warn('ML service refused the reload (HTTP '.$response->status().').');
        } catch (ConnectionException) {
            $this->warn('ML service is not running; it will load the taxa on next start.');
        }

        return self::SUCCESS;
    }
}
