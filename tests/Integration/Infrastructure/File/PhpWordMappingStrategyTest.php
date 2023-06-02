<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Integration\Infrastructure\File;

use Psr\Log\NullLogger;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\MismatchedMapItemDepthException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToFindRelatedSegmentsException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToIdentifyDefaultMapItemTypeException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToIdentifyLastSegmentKeyException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnknownMapItemTypeReceivedException;
use Smoothie\ResumeExporter\Infrastructure\File\PhpWordMappingStrategy;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemRepository;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemsFactory;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessStrategy;
use Smoothie\Tests\ResumeExporter\BasicTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @group integration
 * @group integration-infrastructure
 * @group integration-infrastructure-file
 * @group integration-infrastructure-file-php-word-mapping-strategy
 */
class PhpWordMappingStrategyTest extends BasicTestCase
{
    /**
     * @dataProvider provideNormalizeNotSoGoodPath
     */
    public function testNormalizeThrowsHard(array $assertions, array $expectations): void
    {
        $phpWordMappingStrategy = $this->buildPhpWordMappingStrategy();

        $this->expectException($expectations['exception']);

        $phpWordMappingStrategy->normalize(
            map: $assertions['map'],
            from: $assertions['from'],
            settings: $assertions['settings'],
        );
    }

    /**
     * @dataProvider provideNormalizeGoodPath
     */
    public function testNormalize(array $assertions, array $expectations): void
    {
        $phpWordMappingStrategy = $this->buildPhpWordMappingStrategy();
        $outputMap = $phpWordMappingStrategy->normalize(
            map: $assertions['map'],
            from: $assertions['from'],
            settings: $assertions['settings'],
        );

        static::assertSame(expected: $expectations['outputMap'], actual: $outputMap);
    }

    public function buildPhpWordMappingStrategy(): PhpWordMappingStrategy
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $logger = new NullLogger();

        $propertyAccessMappingStrategy = new PropertyAccessStrategy(
            propertyAccessor: $propertyAccessor,
            logger: $logger,
            propertyAccessMapItemsFactory: new PropertyAccessMapItemsFactory(),
            mapItemRepository: new PropertyAccessMapItemRepository(propertyAccessor: $propertyAccessor),
        );

        return new PhpWordMappingStrategy(
            mappingStrategy: $propertyAccessMappingStrategy,
            propertyAccessor: $propertyAccessor,
        );
    }

    private function provideNormalizeNotSoGoodPath(): array
    {
        return [
            'when_table_first_column_is_an_array' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro][*][*]' => '[introduction][*][*]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => [['An introduction']],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction][*]' => 'TABLE',
                            '[introduction][*][*]' => 'TABLE_ROW',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToIdentifyLastSegmentKeyException::class,
                ],
            ],
            'when_table_has_no_deeper_nested_items' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro][*]' => '[introduction][*]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => ['An introduction'],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction][*]' => 'TABLE',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToFindRelatedSegmentsException::class,
                ],
            ],
            'when_default_map_item_type_is_unknown' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                    ],
                    'from' => [
                        'canonical' => [
                            ['intro' => 'An introduction'],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            'default' => 'WOOT',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => UnknownMapItemTypeReceivedException::class,
                ],
            ],
            'when_no_default_is_specified_and_at_least_one_item_has_no_type_setting' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                    ],
                    'from' => [
                        'canonical' => [
                            ['intro' => 'An introduction'],
                        ],
                    ],
                    'settings' => [
                        'types' => [],
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToIdentifyDefaultMapItemTypeException::class,
                ],
            ],
            'when_we_receive_an_unknown_map_item' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                    ],
                    'from' => [
                        'canonical' => [
                            ['intro' => 'An introduction'],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'WOOT',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => UnknownMapItemTypeReceivedException::class,
                ],
            ],
            'when_from_item_is_array_but_to_item_is_not' => [
                'assertions' => [
                    'map' => [
                        '[canonical][*][intro]' => '[introduction]',
                    ],
                    'from' => [
                        'canonical' => [
                            ['intro' => 'An introduction'],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'VALUE',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => MismatchedMapItemDepthException::class,
                ],
            ],
            'when_from_item_has_higher_depth_than_to_item' => [
                'assertions' => [
                    'map' => [
                        '[canonical][*][intro][*]' => '[introduction][*]',
                    ],
                    'from' => [
                        'canonical' => [
                            ['intro' => ['An introduction']],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'VALUE',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => MismatchedMapItemDepthException::class,
                ],
            ],
            'when_from_item_is_not_an_array_but_to_item_is' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction][*]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => 'An introduction',
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'VALUE',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => MismatchedMapItemDepthException::class,
                ],
            ],
            'when_from_item_has_lower_depth_than_to_item' => [
                'assertions' => [
                    'map' => [
                        '[canonical][*][intro]' => '[some][*][introduction][*]',
                    ],
                    'from' => [
                        'canonical' => [
                            ['intro' => 'An introduction'],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'VALUE',
                        ],
                    ],
                ],
                'expectations' => [
                    'exception' => MismatchedMapItemDepthException::class,
                ],
            ],
        ];
    }

    private function provideNormalizeGoodPath(): array
    {
        return [
            'explicit_one_value' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => 'An introduction',
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'VALUE',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][intro]' => '[0][values][introduction]',
                    ],
                ],
            ],
            'explicit_two_values' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                        '[canonical][rand]' => '[random]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => 'An introduction',
                            'rand' => 'I_WAS_REPLACED',
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'VALUE',
                            '[random]' => 'VALUE',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][intro]' => '[0][values][introduction]',
                        '[canonical][rand]' => '[0][values][random]',
                    ],
                ],
            ],
            'explicit_one_block_with_one_item' => [
                'assertions' => [
                    'map' => [
                        '[canonical][nestedBlocks][*]' => '[aNestedBlock][*]',
                        '[canonical][nestedBlocks][*][nestedValue]' => '[aNestedBlock][*][aNestedValue]',
                    ],
                    'from' => [
                        'canonical' => [
                            'nestedBlocks' => [
                                ['nestedValue' => 'FIRST_NESTED_VALUE'],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[aNestedBlock][*]' => 'BLOCK',
                            '[aNestedBlock][*][aNestedValue]' => 'BLOCK_ITEM',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][nestedBlocks][0]' => '[0][blocks][aNestedBlock][0]',
                        '[canonical][nestedBlocks][0][nestedValue]' => '[0][blocks][aNestedBlock][0][aNestedValue]',
                    ],
                ],
            ],
            'explicit_one_block_with_three_items' => [
                'assertions' => [
                    'map' => [
                        '[canonical][nestedBlocks][*]' => '[aNestedBlock][*]',
                        '[canonical][nestedBlocks][*][nestedValue]' => '[aNestedBlock][*][aNestedValue]',
                    ],
                    'from' => [
                        'canonical' => [
                            'nestedBlocks' => [
                                ['nestedValue' => 'FIRST_NESTED_VALUE'],
                                ['nestedValue' => 'SECOND_NESTED_VALUE'],
                                ['nestedValue' => 'THIRD_NESTED_VALUE'],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[aNestedBlock][*]' => 'BLOCK',
                            '[aNestedBlock][*][aNestedValue]' => 'BLOCK_ITEM',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][nestedBlocks][0]' => '[0][blocks][aNestedBlock][0]',
                        '[canonical][nestedBlocks][1]' => '[0][blocks][aNestedBlock][1]',
                        '[canonical][nestedBlocks][2]' => '[0][blocks][aNestedBlock][2]',
                        '[canonical][nestedBlocks][0][nestedValue]' => '[0][blocks][aNestedBlock][0][aNestedValue]',
                        '[canonical][nestedBlocks][1][nestedValue]' => '[0][blocks][aNestedBlock][1][aNestedValue]',
                        '[canonical][nestedBlocks][2][nestedValue]' => '[0][blocks][aNestedBlock][2][aNestedValue]',
                    ],
                ],
            ],
            'explicit_two_blocks_with_one_item_each' => [
                'assertions' => [
                    'map' => [
                        '[canonical][nestedBlocks][*]' => '[aNestedBlock][*]',
                        '[canonical][nestedBlocks][*][nestedValue]' => '[aNestedBlock][*][aNestedValue]',
                        '[canonical][secondNestedBlocks][*]' => '[anotherNestedBlock][*]',
                        '[canonical][secondNestedBlocks][*][foo]' => '[anotherNestedBlock][*][aFoo]',
                    ],
                    'from' => [
                        'canonical' => [
                            'nestedBlocks' => [
                                ['nestedValue' => 'FIRST_NESTED_VALUE'],
                            ],
                            'secondNestedBlocks' => [
                                ['foo' => 'FIRST_NESTED_VALUE'],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[aNestedBlock][*]' => 'BLOCK',
                            '[aNestedBlock][*][aNestedValue]' => 'BLOCK_ITEM',
                            '[anotherNestedBlock][*]' => 'BLOCK',
                            '[anotherNestedBlock][*][aFoo]' => 'BLOCK_ITEM',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][nestedBlocks][0]' => '[0][blocks][aNestedBlock][0]',
                        '[canonical][nestedBlocks][0][nestedValue]' => '[0][blocks][aNestedBlock][0][aNestedValue]',
                        '[canonical][secondNestedBlocks][0]' => '[0][blocks][anotherNestedBlock][0]',
                        '[canonical][secondNestedBlocks][0][foo]' => '[0][blocks][anotherNestedBlock][0][aFoo]',
                    ],
                ],
            ],
            'explicit_two_blocks_with_three_items_and_one_item' => [
                'assertions' => [
                    'map' => [
                        '[canonical][nestedBlocks][*]' => '[aNestedBlock][*]',
                        '[canonical][nestedBlocks][*][nestedValue]' => '[aNestedBlock][*][aNestedValue]',
                        '[canonical][secondNestedBlocks][*]' => '[anotherNestedBlock][*]',
                        '[canonical][secondNestedBlocks][*][foo]' => '[anotherNestedBlock][*][aFoo]',
                    ],
                    'from' => [
                        'canonical' => [
                            'nestedBlocks' => [
                                ['nestedValue' => 'FIRST_NESTED_VALUE'],
                                ['nestedValue' => 'SECOND_NESTED_VALUE'],
                                ['nestedValue' => 'THIRD_NESTED_VALUE'],
                            ],
                            'secondNestedBlocks' => [
                                ['foo' => 'FIRST_NESTED_VALUE'],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[aNestedBlock][*]' => 'BLOCK',
                            '[aNestedBlock][*][aNestedValue]' => 'BLOCK_ITEM',
                            '[anotherNestedBlock][*]' => 'BLOCK',
                            '[anotherNestedBlock][*][aFoo]' => 'BLOCK_ITEM',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][nestedBlocks][0]' => '[0][blocks][aNestedBlock][0]',
                        '[canonical][nestedBlocks][1]' => '[0][blocks][aNestedBlock][1]',
                        '[canonical][nestedBlocks][2]' => '[0][blocks][aNestedBlock][2]',
                        '[canonical][nestedBlocks][0][nestedValue]' => '[0][blocks][aNestedBlock][0][aNestedValue]',
                        '[canonical][nestedBlocks][1][nestedValue]' => '[0][blocks][aNestedBlock][1][aNestedValue]',
                        '[canonical][nestedBlocks][2][nestedValue]' => '[0][blocks][aNestedBlock][2][aNestedValue]',
                        '[canonical][secondNestedBlocks][0]' => '[0][blocks][anotherNestedBlock][0]',
                        '[canonical][secondNestedBlocks][0][foo]' => '[0][blocks][anotherNestedBlock][0][aFoo]',
                    ],
                ],
            ],
            'explicit_one_value_and_one_block_with_one_item' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                        '[canonical][nestedBlocks][*]' => '[aNestedBlock][*]',
                        '[canonical][nestedBlocks][*][nestedValue]' => '[aNestedBlock][*][aNestedValue]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => 'An introduction',
                            'nestedBlocks' => [
                                ['nestedValue' => 'FIRST_NESTED_VALUE'],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[introduction]' => 'VALUE',
                            '[aNestedBlock][*]' => 'BLOCK',
                            '[aNestedBlock][*][aNestedValue]' => 'BLOCK_ITEM',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][intro]' => '[0][values][introduction]',
                        '[canonical][nestedBlocks][0]' => '[0][blocks][aNestedBlock][0]',
                        '[canonical][nestedBlocks][0][nestedValue]' => '[0][blocks][aNestedBlock][0][aNestedValue]',
                    ],
                ],
            ],
            'explicit_one_table_row_with_one_column' => [
                'assertions' => [
                    'map' => [
                        '[canonical][tableWhat][*]' => '[what][*]',
                        '[canonical][tableWhat][*][tableWhat]' => '[what][*][what]',
                    ],
                    'from' => [
                        'canonical' => [
                            'tableWhat' => [
                                [
                                    'tableWhat' => 'FIRST_WHAT',
                                ],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[what][*]' => 'TABLE',
                            '[what][*][what]' => 'TABLE_ROW',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][tableWhat][0]' => '[0][rows][what][0]',
                        '[canonical][tableWhat][0][tableWhat]' => '[0][rows][what][0][what]',
                    ],
                ],
            ],
            'explicit_one_table_row_with_three_columns' => [
                'assertions' => [
                    'map' => [
                        '[canonical][tableWhat][*]' => '[what][*]',
                        '[canonical][tableWhat][*][tableWhat]' => '[what][*][what]',
                        '[canonical][tableWhat][*][tableHow]' => '[what][*][how]',
                        '[canonical][tableWhat][*][tableWhy]' => '[what][*][why]',
                    ],
                    'from' => [
                        'canonical' => [
                            'tableWhat' => [
                                [
                                    'tableWhat' => 'FIRST_WHAT',
                                    'tableHow' => 'FIRST_HOW',
                                    'tableWhy' => 'FIRST_WHY',
                                ],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[what][*]' => 'TABLE',
                            '[what][*][what]' => 'TABLE_ROW',
                            '[what][*][how]' => 'TABLE_ROW',
                            '[what][*][why]' => 'TABLE_ROW',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][tableWhat][0]' => '[0][rows][what][0]',
                        '[canonical][tableWhat][0][tableWhat]' => '[0][rows][what][0][what]',
                        '[canonical][tableWhat][0][tableHow]' => '[0][rows][what][0][how]',
                        '[canonical][tableWhat][0][tableWhy]' => '[0][rows][what][0][why]',
                    ],
                ],
            ],
            'explicit_one_block_with_one_table_row_containing_one_column' => [
                'assertions' => [
                    'map' => [
                        '[canonical][moreBlocks][*]' => '[anotherNestedBlock][*]',
                        '[canonical][moreBlocks][*][nestedTableRows][*]' => '[anotherNestedBlock][*][first][*]',
                        '[canonical][moreBlocks][*][nestedTableRows][*][first]' => '[anotherNestedBlock][*][first][*][first]',
                    ],
                    'from' => [
                        'canonical' => [
                            'moreBlocks' => [
                                [
                                    'nestedTableRows' => [
                                        [
                                            'first' => 'FIRST_FIRST_FIRST',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[anotherNestedBlock][*]' => 'BLOCK',
                            '[anotherNestedBlock][*][first][*]' => 'TABLE',
                            '[anotherNestedBlock][*][first][*][first]' => 'TABLE_ROW',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[internal][emptyFields][1][values]' => '[1][values]',
                        '[internal][emptyFields][1][blocks]' => '[1][blocks]',
                        '[internal][emptyFields][1][rows]' => '[1][rows]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][0]' => '[0][blocks][anotherNestedBlock][0][first][0]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][0][first]' => '[0][blocks][anotherNestedBlock][0][first][0][first]',
                        '[canonical][moreBlocks][0]' => '[0][blocks][anotherNestedBlock][0]',
                        '[canonical][moreBlocks][0][nestedTableRows][0]' => '[1][rows][anotherNestedBlock][0][first][0]',
                        '[canonical][moreBlocks][0][nestedTableRows][0][first]' => '[1][rows][anotherNestedBlock][0][first][0][first]',
                    ],
                ],
            ],
            'explicit_one_block_with_one_table_row_containing_one_implicit_column' => [
                // PhpWord searches for a column name and replaces that, therefor the column must be available
                // But this is an infrastructure dependency -> users shouldn't need to care about that
                'assertions' => [
                    'map' => [
                        '[canonical][moreBlocks][*]' => '[anotherNestedBlock][*]',
                        '[canonical][moreBlocks][*][nestedTableRows][*]' => '[anotherNestedBlock][*][aNestedTableRow][*]',
                        '[canonical][moreBlocks][*][nestedTableRows][*][first]' => '[anotherNestedBlock][*][aNestedTableRow][*][first]',
                    ],
                    'from' => [
                        'canonical' => [
                            'moreBlocks' => [
                                [
                                    'nestedTableRows' => [
                                        [
                                            'first' => 'FIRST_FIRST_FIRST',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            '[anotherNestedBlock][*]' => 'BLOCK',
                            '[anotherNestedBlock][*][aNestedTableRow][*]' => 'TABLE',
                            '[anotherNestedBlock][*][aNestedTableRow][*][first]' => 'TABLE_ROW',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[internal][emptyFields][1][values]' => '[1][values]',
                        '[internal][emptyFields][1][blocks]' => '[1][blocks]',
                        '[internal][emptyFields][1][rows]' => '[1][rows]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][0]' => '[0][blocks][anotherNestedBlock][0][first][0]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][0][first]' => '[0][blocks][anotherNestedBlock][0][first][0][first]',
                        '[canonical][moreBlocks][0]' => '[0][blocks][anotherNestedBlock][0]',
                        '[canonical][moreBlocks][0][nestedTableRows][0]' => '[1][rows][anotherNestedBlock][0][first][0]',
                        '[canonical][moreBlocks][0][nestedTableRows][0][first]' => '[1][rows][anotherNestedBlock][0][first][0][first]',
                    ],
                ],
            ],
            'default_value' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => 'An introduction',
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            'default' => 'VALUE',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[canonical][intro]' => '[0][values][introduction]',
                    ],
                ],
            ],
            'template_values_blocks_and_rows' => [
                'assertions' => [
                    'map' => [
                        '[canonical][intro]' => '[introduction]',
                        '[canonical][rand]' => '[random]',
                        '[canonical][nestedBlocks][*]' => '[aNestedBlock][*]',
                        '[canonical][nestedBlocks][*][nestedValue]' => '[aNestedBlock][*][aNestedValue]',
                        '[canonical][tableWhat][*]' => '[what][*]',
                        '[canonical][tableWhat][*][tableWhat]' => '[what][*][what]',
                        '[canonical][tableWhat][*][tableHow]' => '[what][*][how]',
                        '[canonical][tableWhat][*][tableWhy]' => '[what][*][why]',
                        '[canonical][moreBlocks][*]' => '[anotherNestedBlock][*]',
                        '[canonical][moreBlocks][*][nestedTableRows][*]' => '[anotherNestedBlock][*][aNestedTableRow][*]',
                        '[canonical][moreBlocks][*][nestedTableRows][*][first]' => '[anotherNestedBlock][*][aNestedTableRow][*][first]',
                        '[canonical][moreBlocks][*][nestedTableRows][*][second]' => '[anotherNestedBlock][*][aNestedTableRow][*][second]',
                    ],
                    'from' => [
                        'canonical' => [
                            'intro' => 'An introduction',
                            'rand' => 'I_WAS_REPLACED',
                            'nestedBlocks' => [
                                ['nestedValue' => 'FIRST_NESTED_VALUE'],
                                ['nestedValue' => 'SECOND_NESTED_VALUE'],
                                ['nestedValue' => 'THIRD_NESTED_VALUE'],
                            ],
                            'tableWhat' => [
                                [
                                    'tableWhat' => 'FIRST_WHAT',
                                    'tableHow' => 'FIRST_HOW',
                                    'tableWhy' => 'FIRST_WHY',
                                ],
                                [
                                    'tableWhat' => 'SECOND_WHAT',
                                    'tableHow' => 'SECOND_HOW',
                                    'tableWhy' => 'SECOND_WHY',
                                ],
                            ],
                            'moreBlocks' => [
                                [
                                    'nestedTableRows' => [
                                        [
                                            'first' => 'FIRST_FIRST_FIRST',
                                            'second' => 'FIRST_FIRST_SECOND',
                                        ],
                                        [
                                            'first' => 'FIRST_SECOND_FIRST',
                                            'second' => 'FIRST_SECOND_SECOND',
                                        ],
                                        [
                                            'first' => 'FIRST_THIRD_FIRST',
                                            'second' => 'FIRST_THIRD_SECOND',
                                        ],
                                    ],
                                ],
                                [
                                    'nestedTableRows' => [
                                        [
                                            'first' => 'SECOND_FIRST_FIRST',
                                            'second' => 'SECOND_FIRST_SECOND',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'settings' => [
                        'types' => [
                            'default' => 'VALUE',
                            '[aNestedBlock][*]' => 'BLOCK',
                            '[aNestedBlock][*][aNestedValue]' => 'BLOCK_ITEM',
                            '[what][*]' => 'TABLE',
                            '[what][*][what]' => 'TABLE_ROW',
                            '[what][*][how]' => 'TABLE_ROW',
                            '[what][*][why]' => 'TABLE_ROW',
                            '[anotherNestedBlock][*]' => 'BLOCK',
                            '[anotherNestedBlock][*][aNestedTableRow][*]' => 'TABLE',
                            '[anotherNestedBlock][*][aNestedTableRow][*][first]' => 'TABLE_ROW',
                            '[anotherNestedBlock][*][aNestedTableRow][*][second]' => 'TABLE_ROW',
                        ],
                    ],
                ],
                'expectations' => [
                    'outputMap' => [
                        '[internal][emptyFields][0][values]' => '[0][values]',
                        '[internal][emptyFields][0][blocks]' => '[0][blocks]',
                        '[internal][emptyFields][0][rows]' => '[0][rows]',
                        '[internal][emptyFields][1][values]' => '[1][values]',
                        '[internal][emptyFields][1][blocks]' => '[1][blocks]',
                        '[internal][emptyFields][1][rows]' => '[1][rows]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][0]' => '[0][blocks][anotherNestedBlock][0][first][0]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][1]' => '[0][blocks][anotherNestedBlock][0][first][1]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][2]' => '[0][blocks][anotherNestedBlock][0][first][2]',
                        '[internal][nestedRows][canonical][moreBlocks][1][nestedTableRows][0]' => '[0][blocks][anotherNestedBlock][1][first][0]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][0][first]' => '[0][blocks][anotherNestedBlock][0][first][0][first]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][1][first]' => '[0][blocks][anotherNestedBlock][0][first][1][first]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][2][first]' => '[0][blocks][anotherNestedBlock][0][first][2][first]',
                        '[internal][nestedRows][canonical][moreBlocks][1][nestedTableRows][0][first]' => '[0][blocks][anotherNestedBlock][1][first][0][first]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][0][second]' => '[0][blocks][anotherNestedBlock][0][first][0][second]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][1][second]' => '[0][blocks][anotherNestedBlock][0][first][1][second]',
                        '[internal][nestedRows][canonical][moreBlocks][0][nestedTableRows][2][second]' => '[0][blocks][anotherNestedBlock][0][first][2][second]',
                        '[internal][nestedRows][canonical][moreBlocks][1][nestedTableRows][0][second]' => '[0][blocks][anotherNestedBlock][1][first][0][second]',
                        '[canonical][intro]' => '[0][values][introduction]',
                        '[canonical][rand]' => '[0][values][random]',
                        '[canonical][nestedBlocks][0]' => '[0][blocks][aNestedBlock][0]',
                        '[canonical][nestedBlocks][1]' => '[0][blocks][aNestedBlock][1]',
                        '[canonical][nestedBlocks][2]' => '[0][blocks][aNestedBlock][2]',
                        '[canonical][nestedBlocks][0][nestedValue]' => '[0][blocks][aNestedBlock][0][aNestedValue]',
                        '[canonical][nestedBlocks][1][nestedValue]' => '[0][blocks][aNestedBlock][1][aNestedValue]',
                        '[canonical][nestedBlocks][2][nestedValue]' => '[0][blocks][aNestedBlock][2][aNestedValue]',
                        '[canonical][tableWhat][0]' => '[0][rows][what][0]',
                        '[canonical][tableWhat][1]' => '[0][rows][what][1]',
                        '[canonical][tableWhat][0][tableWhat]' => '[0][rows][what][0][what]',
                        '[canonical][tableWhat][1][tableWhat]' => '[0][rows][what][1][what]',
                        '[canonical][tableWhat][0][tableHow]' => '[0][rows][what][0][how]',
                        '[canonical][tableWhat][1][tableHow]' => '[0][rows][what][1][how]',
                        '[canonical][tableWhat][0][tableWhy]' => '[0][rows][what][0][why]',
                        '[canonical][tableWhat][1][tableWhy]' => '[0][rows][what][1][why]',
                        '[canonical][moreBlocks][0]' => '[0][blocks][anotherNestedBlock][0]',
                        '[canonical][moreBlocks][1]' => '[0][blocks][anotherNestedBlock][1]',
                        '[canonical][moreBlocks][0][nestedTableRows][0]' => '[1][rows][anotherNestedBlock][0][first][0]',
                        '[canonical][moreBlocks][0][nestedTableRows][1]' => '[1][rows][anotherNestedBlock][0][first][1]',
                        '[canonical][moreBlocks][0][nestedTableRows][2]' => '[1][rows][anotherNestedBlock][0][first][2]',
                        '[canonical][moreBlocks][1][nestedTableRows][0]' => '[1][rows][anotherNestedBlock][1][first][0]',
                        '[canonical][moreBlocks][0][nestedTableRows][0][first]' => '[1][rows][anotherNestedBlock][0][first][0][first]',
                        '[canonical][moreBlocks][0][nestedTableRows][1][first]' => '[1][rows][anotherNestedBlock][0][first][1][first]',
                        '[canonical][moreBlocks][0][nestedTableRows][2][first]' => '[1][rows][anotherNestedBlock][0][first][2][first]',
                        '[canonical][moreBlocks][1][nestedTableRows][0][first]' => '[1][rows][anotherNestedBlock][1][first][0][first]',
                        '[canonical][moreBlocks][0][nestedTableRows][0][second]' => '[1][rows][anotherNestedBlock][0][first][0][second]',
                        '[canonical][moreBlocks][0][nestedTableRows][1][second]' => '[1][rows][anotherNestedBlock][0][first][1][second]',
                        '[canonical][moreBlocks][0][nestedTableRows][2][second]' => '[1][rows][anotherNestedBlock][0][first][2][second]',
                        '[canonical][moreBlocks][1][nestedTableRows][0][second]' => '[1][rows][anotherNestedBlock][1][first][0][second]',
                    ],
                ],
            ],
        ];
    }
}
