<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use PhpOffice\PhpWord\TemplateProcessor;
use Smoothie\ResumeExporter\Application\Resume\ResumeFactory as WriteResumeFactory;
use Smoothie\ResumeExporter\Application\Resume\ResumeRepository;
use Smoothie\ResumeExporter\Domain\Mapping\FilesystemRepository;
use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputMapping;

class PdfResumeRepository implements ResumeRepository, OutputMapping
{
    public function __construct(
        private readonly MappingStrategy $phpWordMappingStrategy,
        private readonly WriteResumeFactory $resumeFactory,
        private readonly FilesystemRepository $filesystemContract,
    ) {
    }

    public function translateFromCanonical(Output $output): array
    {
        $outputMap = $output->getMap();
        $settings = $output->getMapSettings();
        $resumeData = $this->resumeFactory->toArray($output->getCanonical());
        $this->phpWordMappingStrategy->validate(map: $outputMap, from: $resumeData, settings: $settings);
        $map = $this->phpWordMappingStrategy->normalize(map: $outputMap, from: $resumeData, settings: $settings);
        $pdfData = $this->phpWordMappingStrategy->translate(map: $map, from: $resumeData, settings: $settings);

        return $pdfData;
    }

    public function persist(Output $output): void
    {
        $pdfData = $this->translateFromCanonical($output);
        $settings = [
            'types' => [
                'default' => 'VALUE',
                '[aBlocks]' => 'BLOCK',
                '[aNestedBlock]' => 'BLOCK',
//                '[aNestedBlock][*]' => 'BLOCK_ITEM', // implicit and explicit
                '[what]' => 'TABLE',
//                '[what][*]' => 'TABLE_ROW', // implicit and explicit
                '[anotherNestedBlock]' => 'BLOCK',
                '[anotherNestedBlock][*]' => 'TABLE_ROW',
            ],
        ];

        $settings = [
            'types' => [
                [
                    'item' => 'default',
                    'type' => 'VALUE',
                    'level' => 0,
                    'isImplicit' => true,
                    'parent' => 'root',
                    'values' => [
                        'introduction',
                        'random',
                    ],
                ],
                [
                    'item' => '[aBlocks]',
                    'type' => 'BLOCK',
                    'level' => 0,
                    'isImplicit' => false,
                    'parent' => 'root',
                    'values' => [
                        'aBlocks',
                    ],
                ],
                [
                    'item' => '[aNestedBlock]',
                    'type' => 'BLOCK',
                    'level' => 0,
                    'isImplicit' => false,
                    'parent' => 'root',
                    'values' => [
                        'aNestedBlock',
                    ],
                    'children' => [
                        [
                            'item' => '[aNestedBlock][*]',
                            'type' => 'BLOCK_ITEM',
                            'level' => 0,
                            'isImplicit' => true,
                            'parent' => 'aNestedBlock',
                            'values' => [
                                'aNestedValue',
                            ],
                        ],
                    ],
                ],
                [
                    'item' => '[what]',
                    'type' => 'TABLE',
                    'level' => 0,
                    'isImplicit' => false,
                    'parent' => 'root',
                    'values' => [
                        'what',
                    ],
                    'children' => [
                        [
                            'item' => '[what][*]',
                            'type' => 'TABLE_ITEM',
                            'level' => 0,
                            'isImplicit' => true,
                            'parent' => 'what',
                            'values' => [
                                'what',
                                'how',
                                'why',
                            ],
                        ],
                    ],
                ],
                [
                    'item' => '[anotherNestedBlock]',
                    'type' => 'BLOCK',
                    'level' => 0,
                    'parent' => 'root',
                    'isImplicit' => false,
                    'values' => [
                        'anotherNestedBlock',
                    ],
                    'children' => [
                        [
                            'item' => '[anotherNestedBlock][*]',
                            'type' => 'TABLE_ROW',
                            'level' => 1,
                            'isImplicit' => false,
                            'parent' => 'anotherNestedBlock',
                            'values' => [
                                'first',
                                'second',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $out = [];
        $settingKeys = array_keys(array: $settings);
        foreach ($pdfData as $key => $item) {
            $findKeyFn = fn (string $needle): bool => str_contains(haystack: $key, needle: $needle);
            $settingType = array_filter(array: $settings, callback: $findKeyFn, mode: \ARRAY_FILTER_USE_KEY);
        }

        $pdfData = [
            'introduction' => 'A quick introduction of supported template functions.',
            'random' => 'I_WAS_REPLACED',
            'aBlocks' => 2,
            'aNestedBlock' => [
                ['aNestedValue' => 'FIRST'],
                ['aNestedValue' => 'SECOND'],
                ['aNestedValue' => 'THIRD'],
            ],
            'what' => [
                ['what' => 'what_1', 'how' => 'how_1', 'why' => 'because we can'],
                ['what' => 'what_2', 'how' => 'how_2', 'why' => 'because we want'],
            ],
            'anotherNestedBlock' => [
                [
                    'nestedTable' => [
                        [
                            'first' => 'first_1',
                            'second' => 'second_1',
                        ],
                    ],
                ],
                [
                    'nestedTable' => [
                        [
                            'first' => 'first_2',
                            'second' => 'second_2',
                        ],
                    ],
                ],
                [
                    'nestedTable' => [
                        [
                            'first' => 'first_3',
                            'second' => 'second_3',
                        ],
                    ],
                ],
            ],
        ];

        $pdfData = [
            0 => [
                'values' => [
                    'introduction' => 'A quick introduction of supported template functions.',
                    'random' => 'I_WAS_REPLACED',
                ],
                'blocks' => [
                    'aBlock' => 2,
                    'aNestedBlock' => [
                        ['aNestedValue' => 'FIRST'],
                        ['aNestedValue' => 'SECOND'],
                        ['aNestedValue' => 'THIRD'],
                    ],
                    'anotherNestedBlock' => [
                        ['first' => '${anotherNestedBlock_first_1}', 'second' => '${anotherNestedBlock_second_1}'],
                        ['first' => '${anotherNestedBlock_first_2}', 'second' => '${anotherNestedBlock_second_2}'],
                        ['first' => '${anotherNestedBlock_first_3}', 'second' => '${anotherNestedBlock_second_3}'],
                    ],
                ],
                'rows' => [
                    'what' => [
                        ['what' => 'what_1', 'how' => 'how_1', 'why' => 'because we can'],
                        ['what' => 'what_2', 'how' => 'how_2', 'why' => 'because we want'],
                    ],
                ],
            ],
            1 => [
                'values' => [],
                'blocks' => [],
                'rows' => [
                    'anotherNestedBlock_first_1' => [
                        ['anotherNestedBlock_first_1' => 'first_1', 'anotherNestedBlock_second_1' => 'second_1'],
                    ],
                    'anotherNestedBlock_first_2' => [
                        ['anotherNestedBlock_first_2' => 'first_2', 'anotherNestedBlock_second_2' => 'second_2'],
                    ],
                    'anotherNestedBlock_first_3' => [
                        ['anotherNestedBlock_first_3' => 'first_3', 'anotherNestedBlock_second_3' => 'second_3'],
                    ],
                ],
            ],
        ];
        $this->filesystemContract->exists($output->outputTemplatePath());
        $this->filesystemContract->exists($output->outputPath());

        foreach ($pdfData as $processRuns => $data) {
            $template = $processRuns === 0 ? $output->outputTemplatePath() : $output->outputPath();

            $templateProcessor = new TemplateProcessor($template);

            $templateProcessor->setValues($data['values']);
            foreach ($data['blocks'] as $blockName => $block) {
                if (\is_int($block)) {
                    $templateProcessor->cloneBlock(blockname: $blockName, clones: $block);

                    continue;
                }

                if (\is_array($block) === false) {
                    // invalid block
                    throw new \Exception('woot');
                }

                $templateProcessor->cloneBlock(
                    blockname: $blockName,
                    clones: 0,
                    variableReplacements: $block,
                );
            }

            foreach ($data['rows'] as $rowName => $row) {
                $templateProcessor->cloneRowAndSetValues(search: $rowName, values: $row);
            }

            $templateProcessor->saveAs($output->outputPath());
        }
        //        $this->pdfFilesystemRepository->save($output->outputSource(), $output->getMapSettings(), $pdfData);
    }
}
