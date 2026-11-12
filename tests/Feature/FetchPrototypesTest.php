<?php

namespace Tests\Feature;

use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchPrototypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_taxa_includes_aliases_and_philippine_metadata(): void
    {
        Http::fake([
            '*/v1/taxa/reload' => Http::response(['taxa_count' => 2]),
        ]);

        Plant::factory()->create([
            'scientific_name' => 'Vitex negundo',
            'family' => 'Lamiaceae',
            'common_name' => 'Five-leaved Chaste Tree',
            'common_names' => 'Five-leaved Chaste Tree, Lagundi, Dangla',
            'native_range' => 'Philippines and East Asia',
            'medicinal_rating' => 4,
        ]);

        Plant::factory()->create([
            'scientific_name' => 'Moringa oleifera',
            'family' => 'Moringaceae',
            'common_name' => 'Horseradish Tree',
            'common_names' => 'Malunggay, Marunggay',
            'native_range' => 'India',
            'distribution' => 'Cultivated in the Philippines',
            'medicinal_rating' => 5,
        ]);

        $tempPath = storage_path('framework/testing/test_taxa.json');

        $this->artisan('phytoquest:export-taxa', ['--path' => $tempPath])
            ->assertSuccessful();

        $this->assertFileExists($tempPath);

        $json = json_decode(File::get($tempPath), true);
        $this->assertCount(2, $json);

        $vitex = collect($json)->firstWhere('scientific_name', 'Vitex negundo');
        $this->assertNotNull($vitex);
        $this->assertSame('Five-leaved Chaste Tree', $vitex['common_name']);
        $this->assertEqualsCanonicalizing(['Lagundi', 'Dangla'], $vitex['aliases']);
        $this->assertTrue($vitex['is_philippine']);
        $this->assertSame(4, $vitex['medicinal_rating']);

        $moringa = collect($json)->firstWhere('scientific_name', 'Moringa oleifera');
        $this->assertNotNull($moringa);
        $this->assertEqualsCanonicalizing(['Malunggay', 'Marunggay'], $moringa['aliases']);
        $this->assertTrue($moringa['is_philippine']);

        File::delete($tempPath);
    }
}
