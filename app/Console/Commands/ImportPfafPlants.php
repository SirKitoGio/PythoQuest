<?php

namespace App\Console\Commands;

use App\Models\MedicinalAction;
use App\Models\Plant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * Imports the Plants For A Future (PFAF) medicinal-plant export.
 *
 * The export repeats each species once per medicinal-use keyword, so rows are merged by
 * Latin name. Only the research-safe subset is imported: taxonomy, names, range, hazards
 * and documented traditional medicinal actions. Edible uses, preparation and propagation
 * text are deliberately skipped because PhytoQuest does not give consumption advice.
 * Existing curated profiles are only enriched (blank fields filled), never overwritten.
 */
#[Signature('phytoquest:import-pfaf {path? : CSV export path} {--limit= : Import at most this many species}')]
#[Description('Import plant profiles and traditional-use vocabulary from the PFAF CSV export')]
class ImportPfafPlants extends Command
{
    public const PROVIDER = 'Plants For A Future (PFAF)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path') ?? config('services.pfaf.export_path');

        if (! is_readable($path)) {
            $this->error("PFAF export not found at {$path}.");

            return self::FAILURE;
        }

        $species = $this->readSpecies($path);

        if ($limit = (int) $this->option('limit')) {
            $species = $species->take($limit);
        }

        $retrievedAt = Carbon::createFromTimestamp(filemtime($path));
        $actionIds = $this->syncVocabulary($species);
        $existing = Plant::whereIn('scientific_name', $species->keys())->get()->keyBy('scientific_name');

        $created = $enriched = 0;
        $bar = $this->output->createProgressBar($species->count());

        DB::transaction(function () use ($species, $existing, $actionIds, $retrievedAt, &$created, &$enriched, $bar) {
            $pivotRows = [];

            foreach ($species as $scientificName => $row) {
                $attributes = $this->mapAttributes($scientificName, $row, $retrievedAt);
                $plant = $existing->get($scientificName);

                if ($plant) {
                    $missing = collect($attributes)->filter(fn ($value, $key) => blank($plant->getAttribute($key)));
                    $plant->fill($missing->all())->save();
                    $enriched++;
                } else {
                    $plant = Plant::create($attributes);
                    $created++;
                }

                foreach ($row['actions'] as $action) {
                    $pivotRows[] = [
                        'plant_id' => $plant->id,
                        'medicinal_action_id' => $actionIds[$action],
                        'evidence_level' => 'traditional use',
                        'source_provider' => self::PROVIDER,
                    ];
                }

                $bar->advance();
            }

            foreach (array_chunk($pivotRows, 1000) as $chunk) {
                DB::table('medicinal_action_plant')->insertOrIgnore($chunk);
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Imported {$species->count()} species: {$created} created, {$enriched} existing profiles enriched, ".count($actionIds).' medicinal actions.');

        return self::SUCCESS;
    }

    /**
     * Stream the CSV and merge duplicate rows into one record per species.
     *
     * @return Collection<string, array{row: array<string, string>, actions: array<int, string>}>
     */
    private function readSpecies(string $path): Collection
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $header = null;
        $species = [];

        foreach ($file as $values) {
            if ($header === null) {
                $header = array_map(fn ($column) => trim($column, "\u{FEFF} "), $values);

                continue;
            }

            if (count($values) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $values);
            $name = Str::squish($row['latin_name_search'] ?? '');

            if ($name === '') {
                continue;
            }

            $actions = collect(explode(';', $row['Medicinal Properties'] ?? ''))
                ->push($row['use_keyword'] ?? '')
                ->map(fn ($action) => Str::of($action)->squish()->ucfirst()->toString())
                ->filter(fn ($action) => $action !== '' && mb_strlen($action) <= 60);

            $species[$name]['row'] ??= $row;
            $species[$name]['actions'] = $actions->merge($species[$name]['actions'] ?? [])->unique()->values()->all();
        }

        return collect($species);
    }

    /**
     * Create any missing vocabulary terms and return a name => id map.
     *
     * @param  Collection<string, array{row: array<string, string>, actions: array<int, string>}>  $species
     * @return array<string, int>
     */
    private function syncVocabulary(Collection $species): array
    {
        $now = now();

        $species->pluck('actions')->flatten()->unique()
            ->map(fn ($name) => ['name' => $name, 'slug' => Str::slug($name), 'created_at' => $now, 'updated_at' => $now])
            ->unique('slug')
            ->chunk(500)
            ->each(fn ($chunk) => MedicinalAction::insertOrIgnore($chunk->values()->all()));

        $bySlug = MedicinalAction::pluck('id', 'slug');

        return $species->pluck('actions')->flatten()->unique()
            ->mapWithKeys(fn ($name) => [$name => $bySlug[Str::slug($name)]])
            ->all();
    }

    /**
     * @param  array{row: array<string, string>, actions: array<int, string>}  $record
     * @return array<string, mixed>
     */
    private function mapAttributes(string $scientificName, array $record, Carbon $retrievedAt): array
    {
        $row = $record['row'];
        $commonNames = collect(explode(',', $row['Common Name'] ?: $row['common_name_search'] ?? ''))
            ->map(fn ($name) => Str::squish($name))
            ->filter()
            ->unique(fn ($name) => Str::lower($name))
            ->values();
        $hazards = Str::squish($row['Known Hazards'] ?? '');
        $rating = $row['Medicinal Rating'] ?? '';

        return [
            'common_name' => Str::limit($commonNames->first() ?? $scientificName, 250, ''),
            'scientific_name' => $scientificName,
            'family' => Str::squish($row['Family'] ?? '') ?: null,
            'common_names' => $commonNames->implode(', ') ?: null,
            'category' => 'Medicinal',
            'native_range' => Str::squish($row['Native Range'] ?? '') ?: null,
            'distribution' => Str::limit(Str::squish($row['Range'] ?? ''), 250) ?: null,
            'hardiness_zones' => Str::squish($row['USDA hardiness'] ?? '') ?: null,
            'care_requirements' => Str::limit(Str::squish($row['Care Requirements'] ?? ''), 250) ?: null,
            'medicinal_rating' => is_numeric($rating) ? (int) $rating : null,
            'weed_potential' => match ($row['Weed Potential'] ?? '') {
                'Yes' => true,
                'No' => false,
                default => null,
            },
            'physical_features' => Str::squish($row['Summary'] ?? '') ?: null,
            'known_toxins' => in_array(Str::lower($hazards), ['', 'none known'], true) ? null : $hazards,
            'source_label' => 'trusted database',
            'source_provider' => self::PROVIDER,
            'source_url' => $this->absoluteUrl($row['plant_url'] ?? ''),
            'source_retrieved_at' => $retrievedAt,
            'reference' => 'Plants For A Future database, pfaf.org',
            'image_url' => $this->firstImageUrl($row['Image URLs'] ?? ''),
        ];
    }

    private function firstImageUrl(string $images): ?string
    {
        $image = collect(explode(';', $images))
            ->map(fn ($image) => trim($image))
            ->first(fn ($image) => $image !== '' && ! Str::contains(Str::lower($image), 'noplantimage'));

        return $image ? $this->absoluteUrl(Str::replaceStart('../', '/', $image)) : null;
    }

    private function absoluteUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $url = Str::startsWith($url, '/') ? 'https://pfaf.org'.$url : $url;

        return Str::limit(str_replace(' ', '%20', $url), 255, '');
    }
}
