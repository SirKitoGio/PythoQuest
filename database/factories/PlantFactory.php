<?php

namespace Database\Factories;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plant>
 */
class PlantFactory extends Factory
{
    /**
     * @var array<int, array<int, string>>
     */
    public const SPECIES = [
        ['Lagundi', 'Vitex negundo', 'Medicinal', 'Open fields, riverbanks and lowland thickets', 'leaf, bark, fruit'],
        ['Sambong', 'Blumea balsamifera', 'Medicinal', 'Grasslands and open waste areas at low to medium altitudes', 'leaf, whole plant'],
        ['Tsaang Gubat', 'Ehretia microphylla', 'Shrubs', 'Secondary forests and thickets at low altitudes', 'leaf, fruit'],
        ['Niyog-niyogan', 'Combretum indicum', 'Shrubs', 'Thickets and secondary forests; often cultivated on trellises', 'flower, fruit, leaf'],
        ['Akapulko', 'Senna alata', 'Shrubs', 'Moist open areas, roadsides and settlements', 'leaf, flower'],
        ['Bayabas', 'Psidium guajava', 'Trees', 'Backyards, open lowlands and disturbed areas', 'leaf, bark, fruit'],
        ['Ulasimang Bato', 'Peperomia pellucida', 'Herbal', 'Damp shaded walls, pots and garden beds', 'whole plant'],
        ['Bawang', 'Allium sativum', 'Herbal', 'Cultivated in gardens and farms', 'whole plant'],
        ['Ampalaya', 'Momordica charantia', 'Medicinal', 'Cultivated vines in gardens and farm plots', 'fruit, leaf'],
        ['Yerba Buena', 'Clinopodium douglasii', 'Herbal', 'Cultivated in pots and moist garden soil', 'leaf, stem'],
        ['Malunggay', 'Moringa oleifera', 'Trees', 'Backyards, fence lines and lowland settlements', 'leaf, fruit, bark'],
        ['Banaba', 'Lagerstroemia speciosa', 'Trees', 'Lowland forests; widely planted along streets and parks', 'leaf, bark, flower'],
        ['Neem', 'Azadirachta indica', 'Trees', 'Dry lowlands; planted along roads and campuses', 'leaf, bark, fruit'],
        ['Oregano', 'Coleus amboinicus', 'Herbal', 'Cultivated in pots and home gardens', 'leaf'],
        ['Tawa-tawa', 'Euphorbia hirta', 'Herbal', 'Lawns, roadsides and cracks in pavement', 'whole plant'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$common, $scientific, $category, $habitat, $parts] = fake()->unique()->randomElement(self::SPECIES);

        return [
            'common_name' => $common,
            'scientific_name' => $scientific,
            'category' => $category,
            'habitat' => $habitat,
            'plant_parts' => $parts,
            'physical_features' => fake()->randomElement([
                'Opposite leaves with serrated margins; small clustered flowers.',
                'Aromatic leaves covered in fine hairs; upright woody stem.',
                'Simple glossy leaves; bark smooth to slightly flaking.',
                'Compound leaves with paired leaflets; bright yellow flower spikes.',
                'Succulent, heart-shaped leaves on translucent stems.',
            ]),
            'known_toxins' => fake()->optional(0.7)->randomElement([
                'Saponins and alkaloids reported in some plant parts; laboratory confirmation required.',
                'Latex may irritate skin and eyes.',
                'Seeds reported to contain compounds that are toxic in quantity.',
                'No major toxins documented in reviewed sources; data limited.',
            ]),
            'extract_types' => fake()->randomElement([
                'Aqueous leaf extract; ethanolic leaf extract',
                'Essential oil (steam distillation)',
                'Methanolic bark extract',
                'Ethanolic whole-plant extract',
                'Aqueous fruit extract',
            ]),
            'source_label' => fake()->randomElement(Plant::SOURCE_LABELS),
            'reference' => fake()->randomElement([
                'Plants of the World Online, Royal Botanic Gardens, Kew',
                'World Checklist of Vascular Plants (Govaerts et al., 2021)',
                'GBIF occurrence records, accessed '.fake()->date('M Y'),
                fake()->lastName().' et al., Philippine Journal of Science, '.fake()->numberBetween(2010, 2025),
            ]),
            'image_url' => null,
        ];
    }
}
