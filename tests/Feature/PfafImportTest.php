<?php

namespace Tests\Feature;

use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PfafImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_rows_are_merged_into_one_profile_per_species_with_provenance(): void
    {
        $this->artisan('phytoquest:import-pfaf')->assertSuccessful();

        $this->assertSame(4, Plant::count());

        $vitex = Plant::firstWhere('scientific_name', 'Vitex negundo');
        $this->assertSame('Huang Ping', $vitex->common_name);
        $this->assertSame('Verbenaceae', $vitex->family);
        $this->assertSame('Plants For A Future (PFAF)', $vitex->source_provider);
        $this->assertSame('https://pfaf.org/user/Plant.aspx?LatinName=Vitex%20negundo', $vitex->source_url);
        $this->assertSame('https://pfaf.org/Admin/PlantImages/VitexNegundo.jpg', $vitex->image_url);
        $this->assertEqualsCanonicalizing(
            ['Antibacterial', 'Antitumor', 'Astringent', 'Dysentery', 'Expectorant', 'Febrifuge', 'Malaria', 'Sedative', 'Tonic', 'Vermifuge'],
            $vitex->medicinalActions->pluck('name')->all(),
        );
        $this->assertSame('traditional use', $vitex->medicinalActions->first()->pivot->evidence_level);
    }

    public function test_hazards_are_kept_and_consumption_guidance_is_not_imported(): void
    {
        $this->artisan('phytoquest:import-pfaf')->assertSuccessful();

        $this->assertStringContainsString('toxic', Plant::firstWhere('scientific_name', 'Lantana camara')->known_toxins);
        $this->assertNull(Plant::firstWhere('scientific_name', 'Vitex negundo')->known_toxins);

        foreach (Plant::all() as $plant) {
            foreach ($plant->getAttributes() as $value) {
                $this->assertStringNotContainsString('Edible Parts', (string) $value);
            }
        }
    }

    public function test_curated_profiles_are_enriched_not_overwritten_and_reimport_is_idempotent(): void
    {
        $curated = Plant::factory()->create([
            'common_name' => 'Lagundi',
            'scientific_name' => 'Vitex negundo',
            'category' => 'Medicinal',
            'source_label' => 'research paper',
        ]);

        $this->artisan('phytoquest:import-pfaf')->assertSuccessful();
        $this->artisan('phytoquest:import-pfaf')->assertSuccessful();

        $curated->refresh();
        $this->assertSame('Lagundi', $curated->common_name);
        $this->assertSame('research paper', $curated->source_label);
        $this->assertSame('Verbenaceae', $curated->family);
        $this->assertSame(4, Plant::count());
        $this->assertSame(10, $curated->medicinalActions()->count());
    }

    public function test_missing_export_fails_cleanly(): void
    {
        $this->artisan('phytoquest:import-pfaf', ['path' => '/nonexistent/pfaf.csv'])->assertFailed();
    }
}
