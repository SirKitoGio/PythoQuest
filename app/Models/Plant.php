<?php

namespace App\Models;

use Database\Factories\PlantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'common_name', 'scientific_name', 'family', 'common_names', 'category', 'habitat',
    'native_range', 'distribution', 'hardiness_zones', 'care_requirements',
    'conservation_status', 'medicinal_rating', 'weed_potential', 'plant_parts',
    'physical_features', 'known_toxins', 'extract_types', 'source_label',
    'source_provider', 'source_url', 'source_retrieved_at', 'reference', 'image_url',
])]
class Plant extends Model
{
    /** @use HasFactory<PlantFactory> */
    use HasFactory;

    public const CATEGORIES = ['Medicinal', 'Herbal', 'Shrubs', 'Trees'];

    public const PLANT_PARTS = ['flower', 'leaf', 'stem', 'bark', 'fruit', 'whole plant'];

    public const SOURCE_LABELS = ['trusted database', 'research paper', 'expert-reviewed submission'];

    /**
     * IUCN Red List categories. Vulnerable and above are treated as sensitive.
     *
     * @var array<string, string>
     */
    public const CONSERVATION_STATUSES = [
        'NE' => 'Not evaluated',
        'DD' => 'Data deficient',
        'LC' => 'Least concern',
        'NT' => 'Near threatened',
        'VU' => 'Vulnerable',
        'EN' => 'Endangered',
        'CR' => 'Critically endangered',
    ];

    public const SENSITIVE_STATUSES = ['VU', 'EN', 'CR'];

    protected function casts(): array
    {
        return [
            'weed_potential' => 'boolean',
            'medicinal_rating' => 'integer',
            'source_retrieved_at' => 'datetime',
        ];
    }

    public function sightings(): HasMany
    {
        return $this->hasMany(Sighting::class);
    }

    public function medicinalActions(): BelongsToMany
    {
        return $this->belongsToMany(MedicinalAction::class)->withPivot(['evidence_level', 'source_provider']);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Prefer the locally stored copy; the remote source URL is the fallback.
     */
    public function photoUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk(config('filesystems.media'))->url($this->image_path)
            : $this->image_url;
    }

    public function isSensitive(): bool
    {
        return in_array($this->conservation_status, self::SENSITIVE_STATUSES, true);
    }

    /**
     * Match common, scientific and alternate names. Postgres uses trigram indexes so
     * partial and slightly misspelled names still match; other drivers fall back to LIKE.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        if ($query->getConnection()->getDriverName() !== 'pgsql') {
            $query->where(function (Builder $query) use ($term) {
                foreach (['common_name', 'scientific_name', 'common_names', 'family'] as $column) {
                    $query->orWhere($column, 'like', "%{$term}%");
                }
            });

            return;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(function (Builder $query) use ($term, $like) {
            $query->whereRaw('common_name ILIKE ?', [$like])
                ->orWhereRaw('scientific_name ILIKE ?', [$like])
                ->orWhereRaw('common_names ILIKE ?', [$like])
                ->orWhereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$term])
                ->orWhereRaw('scientific_name % ?', [$term])
                ->orWhereRaw('common_name % ?', [$term]);
        })->reorder()->orderByRaw(
            '(common_name ILIKE ? OR scientific_name ILIKE ? OR common_names ILIKE ?) DESC, '
            .'GREATEST(similarity(common_name, ?), similarity(scientific_name, ?), word_similarity(?, coalesce(common_names, \'\'))) DESC, common_name',
            [$like, $like, $like, $term, $term, $term],
        );
    }

    /**
     * Apply the catalogue filters from FR-02.
     *
     * @param  array{category?: ?string, family?: ?string, part?: ?string, action?: ?string, toxins?: ?string, conservation?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when(in_array($filters['category'] ?? null, self::CATEGORIES, true), fn (Builder $query) => $query->where('category', $filters['category']))
            ->when($filters['family'] ?? null, fn (Builder $query, string $family) => $query->where('family', $family))
            ->when(in_array($filters['part'] ?? null, self::PLANT_PARTS, true), fn (Builder $query) => $query->where('plant_parts', 'like', "%{$filters['part']}%"))
            ->when($filters['action'] ?? null, fn (Builder $query, string $slug) => $query->whereRelation('medicinalActions', 'slug', $slug))
            ->when(($filters['toxins'] ?? null) === 'documented', fn (Builder $query) => $query->whereNotNull('known_toxins'))
            ->when(($filters['toxins'] ?? null) === 'none', fn (Builder $query) => $query->whereNull('known_toxins'))
            ->when(array_key_exists($filters['conservation'] ?? '', self::CONSERVATION_STATUSES), fn (Builder $query) => $query->where('conservation_status', $filters['conservation']));
    }
}
