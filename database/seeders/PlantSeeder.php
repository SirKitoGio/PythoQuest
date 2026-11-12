<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class PlantSeeder extends Seeder
{
    /**
     * Philippine pilot species: Filipino name, scientific name, family, category, habitat, parts studied.
     * Includes the ten DOH-approved herbal plants and common campus trees.
     *
     * @var array<int, array<int, string>>
     */
    public const PILOT_SPECIES = [
        ['Lagundi', 'Vitex negundo', 'Verbenaceae', 'Medicinal', 'Open fields, riverbanks and lowland thickets', 'leaf, bark, fruit'],
        ['Sambong', 'Blumea balsamifera', 'Asteraceae', 'Medicinal', 'Grasslands and open waste areas at low to medium altitudes', 'leaf, whole plant'],
        ['Tsaang Gubat', 'Carmona retusa', 'Boraginaceae', 'Shrubs', 'Secondary forests and thickets at low altitudes', 'leaf, fruit'],
        ['Niyog-niyogan', 'Combretum indicum', 'Combretaceae', 'Shrubs', 'Thickets and secondary forests; often cultivated on trellises', 'flower, fruit, leaf'],
        ['Akapulko', 'Senna alata', 'Fabaceae', 'Shrubs', 'Moist open areas, roadsides and settlements', 'leaf, flower'],
        ['Bayabas', 'Psidium guajava', 'Myrtaceae', 'Trees', 'Backyards, open lowlands and disturbed areas', 'leaf, bark, fruit'],
        ['Ulasimang Bato', 'Peperomia pellucida', 'Piperaceae', 'Herbal', 'Damp shaded walls, pots and garden beds', 'whole plant'],
        ['Bawang', 'Allium sativum', 'Amaryllidaceae', 'Herbal', 'Cultivated in gardens and farms', 'whole plant'],
        ['Ampalaya', 'Momordica charantia', 'Cucurbitaceae', 'Medicinal', 'Cultivated vines in gardens and farm plots', 'fruit, leaf'],
        ['Yerba Buena', 'Mentha cordifolia', 'Lamiaceae', 'Herbal', 'Cultivated in pots and moist garden soil', 'leaf, stem'],
        ['Malunggay', 'Moringa oleifera', 'Moringaceae', 'Trees', 'Backyards, fence lines and lowland settlements', 'leaf, fruit, bark'],
        ['Banaba', 'Lagerstroemia speciosa', 'Lythraceae', 'Trees', 'Lowland forests; widely planted along streets and parks', 'leaf, bark, flower'],
        ['Neem', 'Azadirachta indica', 'Meliaceae', 'Trees', 'Dry lowlands; planted along roads and campuses', 'leaf, bark, fruit'],
        ['Oregano', 'Coleus amboinicus', 'Lamiaceae', 'Herbal', 'Cultivated in pots and home gardens', 'leaf'],
        ['Tawa-tawa', 'Euphorbia hirta', 'Euphorbiaceae', 'Herbal', 'Lawns, roadsides and cracks in pavement', 'whole plant'],
        ['Serpentina', 'Andrographis paniculata', 'Acanthaceae', 'Medicinal', 'Cultivated in gardens; open waste places', 'leaf, whole plant'],
        ['Guyabano', 'Annona muricata', 'Annonaceae', 'Trees', 'Backyards and lowland orchards', 'leaf, fruit'],
        ['Takip-kuhol', 'Centella asiatica', 'Apiaceae', 'Herbal', 'Damp lawns, ditches and rice-field margins', 'leaf, whole plant'],
        ['Makabuhay', 'Tinospora crispa', 'Menispermaceae', 'Medicinal', 'Climbing in thickets and secondary forests at low altitudes', 'stem'],
        ['Pandan', 'Pandanus amaryllifolius', 'Pandanaceae', 'Herbal', 'Cultivated in gardens and pots', 'leaf'],
        ['Gumamela', 'Hibiscus rosa-sinensis', 'Malvaceae', 'Shrubs', 'Widely cultivated in Philippine gardens, hedges and parks', 'flower, leaf'],
        ['Roselle', 'Hibiscus sabdariffa', 'Malvaceae', 'Shrubs', 'Cultivated in lowland gardens and farms', 'flower, leaf, fruit'],
        ['Sabila', 'Aloe vera', 'Asphodelaceae', 'Medicinal', 'Cultivated in pots and sunny garden beds', 'leaf'],
        ['Luya', 'Zingiber officinale', 'Zingiberaceae', 'Medicinal', 'Cultivated in lowland and upland farms and gardens', 'whole plant, leaf'],
        ['Luyang Dilaw', 'Curcuma longa', 'Zingiberaceae', 'Medicinal', 'Cultivated in moist gardens and farms', 'whole plant, leaf'],
        ['Tsitsirika', 'Catharanthus roseus', 'Apocynaceae', 'Herbal', 'Coastal sands, gardens and roadsides throughout the Philippines', 'flower, leaf, whole plant'],
        ['Makahiya', 'Mimosa pudica', 'Fabaceae', 'Herbal', 'Open waste places, lawns, pastures and roadsides', 'leaf, flower, whole plant'],
        ['Papaya', 'Carica papaya', 'Caricaceae', 'Trees', 'Backyards, orchards and clearings throughout the Philippines', 'leaf, fruit, flower'],
        ['Sampalok', 'Tamarindus indica', 'Fabaceae', 'Trees', 'Planted along roadsides, plazas and backyards', 'leaf, fruit, bark'],
        ['Paragis', 'Eleusine indica', 'Poaceae', 'Herbal', 'Open waste places, roadsides and lawns', 'whole plant, leaf'],
        ['Kogon', 'Imperata cylindrica', 'Poaceae', 'Herbal', 'Open grasslands, slopes and disturbed areas', 'whole plant, leaf'],
        ['Tubang Bakod', 'Jatropha curcas', 'Euphorbiaceae', 'Shrubs', 'Planted as living fences and hedges in rural areas', 'leaf, bark, fruit'],
        ['Tangan-tangan', 'Ricinus communis', 'Euphorbiaceae', 'Shrubs', 'Open waste places, roadsides and idle lands', 'leaf, fruit'],
        ['Siling Labuyo', 'Capsicum frutescens', 'Solanaceae', 'Herbal', 'Cultivated in home gardens and naturalized in clearings', 'fruit, leaf'],
        ['Duhat', 'Syzygium cumini', 'Myrtaceae', 'Trees', 'Lowland forests, backyards and rural orchards', 'fruit, bark, leaf'],
        ['Balimbing', 'Averrhoa carambola', 'Oxalidaceae', 'Trees', 'Cultivated in backyards and orchards', 'fruit, leaf, flower'],
        ['Kamoteng Kahoy', 'Manihot esculenta', 'Euphorbiaceae', 'Shrubs', 'Widely cultivated in farms and home gardens', 'leaf, whole plant'],
        ['Talong', 'Solanum melongena', 'Solanaceae', 'Herbal', 'Cultivated in vegetable gardens and farms', 'fruit, leaf, flower'],
        ['Mangga', 'Mangifera indica', 'Anacardiaceae', 'Trees', 'Planted in orchards, backyards and roadsides throughout the Philippines', 'leaf, fruit, bark'],
        ['Niyog', 'Cocos nucifera', 'Arecaceae', 'Trees', 'Coastal areas, lowlands and plantations throughout the Philippines', 'fruit, leaf, whole plant'],
    ];

    /**
     * Run the database seeds.
     *
     * With the PFAF export available the catalogue is seeded from real data; otherwise
     * it falls back to Faker-generated sample profiles.
     */
    public function run(): void
    {
        if (is_readable(config('services.pfaf.export_path'))) {
            Artisan::call('phytoquest:import-pfaf');
        }

        foreach (self::PILOT_SPECIES as [$filipino, $scientific, $family, $category, $habitat, $parts]) {
            $plant = Plant::firstOrNew(['scientific_name' => $scientific]);

            $plant->fill([
                'common_name' => $filipino,
                'common_names' => collect([$plant->common_name, ...explode(', ', (string) $plant->common_names)])
                    ->filter()
                    ->reject(fn ($name) => Str::lower($name) === Str::lower($filipino))
                    ->unique()
                    ->implode(', ') ?: null,
                'family' => $plant->family ?? $family,
                'category' => $category,
                'habitat' => $habitat,
                'plant_parts' => $parts,
                'source_label' => $plant->exists ? $plant->source_label : 'expert-reviewed submission',
                'reference' => $plant->reference ?? 'PhytoQuest pilot species list; awaiting expert-reviewed references',
            ])->save();
        }
    }
}
