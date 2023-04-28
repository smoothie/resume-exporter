<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Integration\Infrastructure\Mapping;

use Psr\Log\NullLogger;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\InvalidParentFromItemFormatException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\MismatchedMapItemDepthException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToFindParentFromItemException;
use Smoothie\ResumeExporter\Infrastructure\Mapping\Exceptions\UnableToMapException;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemRepository;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemsFactory;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessStrategy;
use Smoothie\Tests\ResumeExporter\BasicTestCase;
use Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping\EmptyMapItemRepository;
use Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping\EmptyMapItemsFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webmozart\Assert\InvalidArgumentException;

/**
 * @group integration
 * @group integration-infrastructure
 * @group integration-infrastructure-mapping
 * @group integration-infrastructure-mapping-property-access-strategy
 */
class PropertyAccessStrategyTest extends BasicTestCase
{
    /**
     * @dataProvider provideNormalizeGoodPath
     */
    public function testNormalize(array $assertions, array $expectations): void
    {
        $propertyAccessStrategy = $this->buildPropertyAccessorStrategy();

        $normalizedMap = $propertyAccessStrategy->normalize(map: $assertions['map'], from: $assertions['from']);

        static::assertSame(expected: $expectations['normalizedMap'], actual: $normalizedMap);
    }

    /**
     * @group integration-infrastructure-mapping-property-access-strategy-unhappy
     * @group integration-infrastructure-mapping-property-access-strategy-unhappy-normalize
     *
     * @dataProvider provideNormalizeNotSoGoodPath
     */
    public function testNormalizeThrowsHard(array $assertions, array $expectations): void
    {
        $propertyAccessStrategy = $this->buildPropertyAccessorStrategy();

        $this->expectException($expectations['exception']);
        $propertyAccessStrategy->normalize(map: $assertions['map'], from: $assertions['from']);
    }

    /**
     * @dataProvider provideTranslateGoodPath
     */
    public function testTranslate(array $assertions, array $expectations): void
    {
        $propertyAccessStrategy = $this->buildPropertyAccessorStrategy();

        $result = $propertyAccessStrategy->translate(map: $assertions['map'], from: $assertions['from']);

        static::assertSame(expected: $expectations['to'], actual: $result);
    }

    /**
     * @dataProvider provideValidateGoodPath
     */
    public function testValidate(array $assertions, array $expectations): void
    {
        $propertyAccessor = \Mockery::mock(PropertyAccessorInterface::class);
        foreach ($expectations['propertyAccessor']['isReadable'] as $expectedPropertyAccessor) {
            $propertyAccessor
                ->shouldReceive('isReadable')
                ->with(
                    $expectations['from'],
                    $expectedPropertyAccessor['fromItem'],
                )->andReturnTrue()
                ->once();
        }

        $propertyAccessStrategy = new PropertyAccessStrategy(
            propertyAccessor: $propertyAccessor,
            logger: new NullLogger(),
            propertyAccessMapItemsFactory: new EmptyMapItemsFactory(),
            mapItemRepository: new EmptyMapItemRepository(),
        );

        $propertyAccessStrategy->validate(map: $assertions['map'], from: $assertions['from']);
    }

    /**
     * @dataProvider provideValidateNotSoGoodPath
     */
    public function testValidateThrowsHard(array $assertions, array $expectations): void
    {
        $expectedPropertyAccessor = $expectations['propertyAccessor'];
        $propertyAccessor = \Mockery::mock(PropertyAccessorInterface::class);

        foreach ($expectedPropertyAccessor['isReadable'] as $expectedPropertyAccessor) {
            $propertyAccessor
                ->shouldReceive('isReadable')
                ->with(
                    $expectations['from'],
                    $expectedPropertyAccessor['fromItem'],
                )->andReturn($expectedPropertyAccessor['return'])
                ->times($expectedPropertyAccessor['countCalled']);
        }

        $propertyAccessStrategy = new PropertyAccessStrategy(
            propertyAccessor: $propertyAccessor,
            logger: new NullLogger(),
            propertyAccessMapItemsFactory: new EmptyMapItemsFactory(),
            mapItemRepository: new EmptyMapItemRepository(),
        );

        $this->expectException($expectations['exception']);
        $propertyAccessStrategy->validate(map: $assertions['map'], from: $assertions['from']);
    }

    public function buildPropertyAccessorStrategy(): PropertyAccessStrategy
    {
        $propertyAccessor = $this->buildPropertyAccessor();

        return new PropertyAccessStrategy(
            propertyAccessor: $propertyAccessor,
            logger: new NullLogger(),
            propertyAccessMapItemsFactory: new PropertyAccessMapItemsFactory(),
            mapItemRepository: new PropertyAccessMapItemRepository(propertyAccessor: $propertyAccessor),
        );
    }

    public function buildPropertyAccessor(): PropertyAccessorInterface
    {
        return PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();
    }

    private function provideNormalizeGoodPath(): array
    {
        return [
            'one_level' => [
                'assertions' => [
                    'map' => ['[some]' => '[foo]', '[else]' => '[bar]'],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => ['[some]' => '[foo]', '[else]' => '[bar]'],
                ],
            ],
            'copy_one_nested_level_item' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one]' => '[to][*][first]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => 'from some'],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one]' => '[to][0][first]',
                    ],
                ],
            ],
            'two_nested_map_items_copy_one' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one]' => '[to][*][first]',
                        '[from][*][two]' => '[to][*][second]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => 'from one', 'two' => 'from two'],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one]' => '[to][0][first]',
                        '[from][0][two]' => '[to][0][second]',
                    ],
                ],
            ],
            'two_nested_map_items_copy_two' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one]' => '[to][*][first]',
                        '[from][*][two]' => '[to][*][second]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => 'from one', 'two' => 'from two'],
                            ['one' => 'from one', 'two' => 'from two'],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one]' => '[to][0][first]',
                        '[from][1][one]' => '[to][1][first]',
                        '[from][0][two]' => '[to][0][second]',
                        '[from][1][two]' => '[to][1][second]',
                    ],
                ],
            ],
            'copy_one_nested_level_item_two_items' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one]' => '[to][*][first]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => 'first item'],
                            ['one' => 'second item'],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one]' => '[to][0][first]',
                        '[from][1][one]' => '[to][1][first]',
                    ],
                ],
            ],
            'copy_one_level_group' => [
                'assertions' => [
                    'map' => [
                        '[from][*]' => '[to][*]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => 'from some'],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0]' => '[to][0]',
                    ],
                ],
            ],
            'copy_one_level_group_three_items' => [
                'assertions' => [
                    'map' => [
                        '[from][*]' => '[to][*]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => 'first item'],
                            ['one' => 'second item'],
                            ['one' => 'third item'],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0]' => '[to][0]',
                        '[from][1]' => '[to][1]',
                        '[from][2]' => '[to][2]',
                    ],
                ],
            ],
            'copy_two_nested_level_item' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one][*][two]' => '[to][*][first][*][second]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => [['two' => 'from some']]],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one][0][two]' => '[to][0][first][0][second]',
                    ],
                ],
            ],
            'copy_two_nested_level_item_with_second_containing_two_items' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one][*][two]' => '[to][*][first][*][second]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => [['two' => 'first some'], ['two' => 'second some']]],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one][0][two]' => '[to][0][first][0][second]',
                        '[from][0][one][1][two]' => '[to][0][first][1][second]',
                    ],
                ],
            ],
            'copy_two_nested_level_item_with_first_and_second_containing_two_items' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one][*][two]' => '[to][*][first][*][second]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            ['one' => [['two' => 'first some'], ['two' => 'second some']]],
                            ['one' => [['two' => 'first some'], ['two' => 'second some']]],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one][0][two]' => '[to][0][first][0][second]',
                        '[from][0][one][1][two]' => '[to][0][first][1][second]',
                        '[from][1][one][0][two]' => '[to][1][first][0][second]',
                        '[from][1][one][1][two]' => '[to][1][first][1][second]',
                    ],
                ],
            ],
            'copy_four_nested_level_item' => [
                'assertions' => [
                    'map' => [
                        '[from][*][one][*][two][*][three][*][four]' => '[to][*][first][*][second][*][third][*][fourth]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'from' => [
                            [
                                'one' => [
                                    [
                                        'two' => [
                                            [
                                                'three' => [
                                                    ['fourth' => 'from some'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'normalizedMap' => [
                        '[else]' => '[bar]',
                        '[from][0][one][0][two][0][three][0][four]' => '[to][0][first][0][second][0][third][0][fourth]',
                    ],
                ],
            ],
        ];
    }

    private function provideNormalizeNotSoGoodPath(): array
    {
        return [
            'when_parent_from_item_not_exists' => [
                'assertions' => [
                    'map' => ['[some][*]' => '[foo][*]', '[else]' => '[bar]'],
                    'from' => [
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToFindParentFromItemException::class,
                ],
            ],
            'when_parent_is_a_string' => [
                'assertions' => [
                    'map' => ['[some][*]' => '[foo][*]', '[else]' => '[bar]'],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => InvalidParentFromItemFormatException::class,
                ],
            ],
            'when_parent_is_not_a_list' => [
                'assertions' => [
                    'map' => ['[some][*]' => '[foo][*]', '[else]' => '[bar]'],
                    'from' => [
                        'some' => ['imaMap' => 'foo'],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => InvalidParentFromItemFormatException::class,
                ],
            ],
            'when_a_mismatched_item_depth_is_requested' => [
                'assertions' => [
                    'map' => ['[some][*][two][*]' => '[foo][*]', '[else]' => '[bar]'],
                    'from' => [
                        'some' => [['two' => 'foo']],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => MismatchedMapItemDepthException::class,
                ],
            ],
            'when_unable_to_find_an_dot_notation' => [
                'assertions' => [
                    'map' => ['[some][*][two][*]' => '[foo][*]', '[else]' => '[bar]'],
                    'from' => [
//                        'some' => [['two' => 'foo']],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => MismatchedMapItemDepthException::class,
                ],
            ],
        ];
    }

    private function provideTranslateGoodPath(): array
    {
        return [
            'one_level' => [
                'assertions' => [
                    'map' => ['[some]' => '[foo]', '[else]' => '[bar]'],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'to' => [
                        'foo' => 'from some',
                        'bar' => 'from else',
                    ],
                ],
            ],
            'from_one_to_nested_second_level' => [
                'assertions' => [
                    'map' => [
                        '[some]' => '[foo]',
                        '[else]' => '[bar][nested]',
                    ],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'to' => [
                        'foo' => 'from some',
                        'bar' => [
                            'nested' => 'from else',
                        ],
                    ],
                ],
            ],
            'from_one_to_nested_fourth_level' => [
                'assertions' => [
                    'map' => [
                        '[some]' => '[foo]',
                        '[else]' => '[bar][nested][deep][deeper]',
                    ],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'to' => [
                        'foo' => 'from some',
                        'bar' => [
                            'nested' => ['deep' => ['deeper' => 'from else']],
                        ],
                    ],
                ],
            ],
            'from_nested_level_to_one_level' => [
                'assertions' => [
                    'map' => [
                        '[some][nested]' => '[foo]',
                        '[else]' => '[bar]',
                    ],
                    'from' => [
                        'some' => ['nested' => 'from some'],
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'to' => [
                        'foo' => 'from some',
                        'bar' => 'from else',
                    ],
                ],
            ],
        ];
    }

    private function provideValidateGoodPath(): array
    {
        return [
            'simple' => [
                'assertions' => [
                    'map' => ['some' => 'foo', 'else' => 'bar'],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [
                            ['fromItem' => 'some'],
                            ['fromItem' => 'else'],
                        ],
                    ],
                ],
            ],
            'LIMITATION_not_normalized_array_wont_check_that_is_readable' => [
                'assertions' => [
                    'map' => ['[from][*][item][*]' => '[foo][*][item][*]', '[else]' => '[bar]'],
                    'from' => [
                        'from' => 'from from',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'from' => [
                        'from' => 'from from',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [
                            ['fromItem' => '[else]'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function provideValidateNotSoGoodPath(): array
    {
        return [
            'invalid_map_from_item' => [
                'assertions' => [
                    'map' => [1 => 'foo', 'else' => 'bar'],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToMapException::class,
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [
                            [
                                'fromItem' => 'else',
                                'return' => true,
                                'countCalled' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'invalid_map_to_item' => [
                'assertions' => [
                    'map' => ['some' => 2, 'else' => 'bar'],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToMapException::class,
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [
                            [
                                'fromItem' => 'else',
                                'return' => true,
                                'countCalled' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'unable_to_find_in_from_item' => [
                'assertions' => [
                    'map' => ['some' => 'foo', 'else' => 'bar'],
                    'from' => [
                        'no_some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToMapException::class,
                    'from' => [
                        'no_some' => 'from some',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [
                            [
                                'fromItem' => 'some',
                                'return' => false,
                                'countCalled' => 1,
                            ],
                            [
                                'fromItem' => 'else',
                                'return' => true,
                                'countCalled' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'received_empty_map' => [
                'assertions' => [
                    'map' => [],
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => InvalidArgumentException::class,
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [
                        ],
                    ],
                ],
            ],
            'received_empty_to' => [
                'assertions' => [
                    'map' => ['some' => 'foo', 'else' => 'bar'],
                    'from' => [],
                ],
                'expectations' => [
                    'exception' => InvalidArgumentException::class,
                    'from' => [
                        'some' => 'from some',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [],
                    ],
                ],
            ],
            'mismatched_array_wildcard_count' => [
                'assertions' => [
                    'map' => ['[from][*][item][*]' => '[foo][*][item]', '[else]' => '[bar]'],
                    'from' => [
                        'from' => 'from some',
                        'else' => 'from else',
                    ],
                ],
                'expectations' => [
                    'exception' => UnableToMapException::class,
                    'from' => [
                        'from' => 'from some',
                        'else' => 'from else',
                    ],
                    'propertyAccessor' => [
                        'isReadable' => [
                            [
                                'fromItem' => '[else]',
                                'return' => true,
                                'countCalled' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
