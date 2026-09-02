<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopicSeeder extends Seeder
{
    /** @var array<string, array<string, string>> */
    private const TOPICS = [
        '5054' => [
            'motion-forces-energy' => 'Motion, Forces and Energy',
            'thermal-physics' => 'Thermal Physics',
            'waves' => 'Waves',
            'electricity-magnetism' => 'Electricity and Magnetism',
            'nuclear-physics' => 'Nuclear Physics',
            'space-physics' => 'Space Physics',
        ],
        '5070' => [
            'experimental-chemistry' => 'Experimental Chemistry',
            'particulate-nature-matter' => 'The Particulate Nature of Matter',
            'atoms-elements-compounds' => 'Atoms, Elements and Compounds',
            'stoichiometry' => 'Stoichiometry',
            'electricity-chemistry' => 'Electricity and Chemistry',
            'chemical-energetics' => 'Chemical Energetics',
            'chemical-reactions' => 'Chemical Reactions',
            'acids-bases-salts' => 'Acids, Bases and Salts',
            'periodic-table' => 'The Periodic Table',
            'metals' => 'Metals',
            'air-water' => 'Air and Water',
            'organic-chemistry' => 'Organic Chemistry',
        ],
        '5090' => [
            'cells' => 'Cells',
            'classification' => 'Classification',
            'movement-substances' => 'Movement of Substances',
            'biological-molecules' => 'Biological Molecules',
            'enzymes' => 'Enzymes',
            'plant-nutrition' => 'Plant Nutrition',
            'transport-plants' => 'Transport in Plants',
            'human-nutrition' => 'Human Nutrition',
            'gas-exchange' => 'Gas Exchange in Humans',
            'respiration' => 'Respiration',
            'transport-humans' => 'Transport in Humans',
            'disease-immunity' => 'Disease and Immunity',
            'excretion' => 'Excretion',
            'coordination-response' => 'Coordination and Response',
            'reproduction' => 'Reproduction',
            'inheritance' => 'Inheritance',
            'organisms-environment' => 'Organisms and Their Environment',
            'human-influences' => 'Human Influences on Ecosystems',
        ],
        '4024' => [
            'number' => 'Number',
            'set-language-notation' => 'Set Language and Notation',
            'algebra' => 'Algebra',
            'functions-graphs' => 'Functions and Graphs',
            'coordinate-geometry' => 'Coordinate Geometry',
            'geometry' => 'Geometry',
            'mensuration' => 'Mensuration',
            'trigonometry' => 'Trigonometry',
            'transformations-vectors' => 'Transformations and Vectors',
            'probability' => 'Probability',
            'statistics' => 'Statistics',
        ],
        '2210' => [
            'data-representation' => 'Data Representation',
            'data-transmission' => 'Data Transmission',
            'hardware' => 'Hardware',
            'software' => 'Software',
            'internet-uses' => 'The Internet and Its Uses',
            'automated-emerging-technologies' => 'Automated and Emerging Technologies',
            'algorithm-design' => 'Algorithm Design and Problem-solving',
            'programming' => 'Programming',
            'databases' => 'Databases',
            'boolean-logic' => 'Boolean Logic',
        ],
        '1123' => [
            'reading-comprehension' => 'Reading Comprehension',
            'explicit-implicit-meaning' => 'Explicit and Implicit Meaning',
            'writers-effects' => "Writer's Effects",
            'summary-writing' => 'Summary Writing',
            'vocabulary-context' => 'Vocabulary in Context',
            'directed-writing' => 'Directed Writing',
            'descriptive-writing' => 'Descriptive Writing',
            'narrative-writing' => 'Narrative Writing',
            'argumentative-writing' => 'Argumentative Writing',
        ],
    ];

    public function run(): void
    {
        $now = now();
        $subjects = DB::table('subjects')->whereIn('code', array_keys(self::TOPICS))->get(['id', 'code']);

        foreach ($subjects as $subject) {
            $displayOrder = 1;
            foreach (self::TOPICS[$subject->code] as $code => $name) {
                DB::table('topics')->updateOrInsert(
                    ['subject_id' => $subject->id, 'code' => $code],
                    [
                        'name' => $name,
                        'status' => 'active',
                        'display_order' => $displayOrder++,
                        'metadata' => json_encode([
                            'seeded' => true,
                            'subject_code' => $subject->code,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }
}
