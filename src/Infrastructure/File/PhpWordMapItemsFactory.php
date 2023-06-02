<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\MismatchedMapItemDepthException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToFindRelatedSegmentsException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToIdentifyDefaultMapItemTypeException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToIdentifyLastSegmentKeyException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnknownMapItemTypeReceivedException;
use Smoothie\ResumeExporter\Domain\Mapping\MapItem;
use Smoothie\ResumeExporter\Domain\Mapping\MapItems;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemsFactory;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemType;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessStrategy;
use Webmozart\Assert\Assert;

class PhpWordMapItemsFactory implements MapItemsFactory
{
    final public const REGEX_FIND_ARRAY_INDEX = '/\[[0-9]]+/';
    final public const REGEX_SPLIT_INTO_SEGMENTS = '/\[([^*\[\]]+)]\[\*]/';
    final public const REGEX_FIND_LAST_SEGMENT_VALUE = '/\[([^*\[\]]+)]$/';

    final public const MAP_ITEM_TYPE_ROWS = 'rows';
    final public const MAP_ITEM_TYPE_VALUES = 'values';
    final public const MAP_ITEM_TYPE_BLOCKS = 'blocks';

    public function createMapItems(array $map, array $settings): MapItems
    {
        $mapItems = [];
        $internalNestedRowMapItems = [];
        $internalEmptyFieldsMapItems = [];
        $settingTypes = $settings['types'];

        $allToItems = [];
        $phpWordPersistenceRuns = 1;

        foreach ($map as $toItem) {
            $toItemWithDotNotation = preg_replace(
                self::REGEX_FIND_ARRAY_INDEX,
                PropertyAccessStrategy::WILDCARD,
                $toItem,
            );

            if (\in_array(needle: $toItemWithDotNotation, haystack: $allToItems, strict: true)) {
                continue;
            }

            $allToItems[] = $toItemWithDotNotation;
        }

        foreach ($map as $fromItem => $toItem) {
            $toItemWithDotNotation = preg_replace(
                self::REGEX_FIND_ARRAY_INDEX,
                PropertyAccessStrategy::WILDCARD,
                $toItem,
            );
            $fromItemWithDotNotation = preg_replace(
                self::REGEX_FIND_ARRAY_INDEX,
                PropertyAccessStrategy::WILDCARD,
                $fromItem,
            );
            $mapItemType = $this->createMapItemType(
                toItemWithDotNotation: $toItemWithDotNotation,
                settingTypes: $settingTypes,
            );

            $isToItemAnArray = $this->hasItemDotNotation($toItemWithDotNotation);
            $isFromItemAnArray = $this->hasItemDotNotation($fromItemWithDotNotation);
            $toItemDepth = mb_substr_count(haystack: $toItemWithDotNotation, needle: PropertyAccessStrategy::WILDCARD);
            $fromItemDepth = mb_substr_count(
                haystack: $fromItemWithDotNotation,
                needle: PropertyAccessStrategy::WILDCARD,
            );

            if ($isFromItemAnArray !== $isToItemAnArray) {
                throw new MismatchedMapItemDepthException(
                    toItem: $toItem,
                    fromItem: $fromItem,
                    toItemDepth: $toItemDepth,
                    fromItemDepth: $fromItemDepth,
                    map: $map,
                );
            }

            if ($isToItemAnArray === false) {
                $mapItem = $this->createMapItem(
                    fromItem: $fromItem,
                    toItem: $this->buildOutputMapToItem(
                        toItem: $toItem,
                        mapItemType: $mapItemType,
                    ),
                    fromItemWithDotNotation: $fromItemWithDotNotation,
                    toItemWithDotNotation: $toItemWithDotNotation,
                    mapItemType: $mapItemType,
                );

                $mapItems[] = $mapItem;

                continue;
            }

            $phpWordRun = $toItemDepth < 1 ? 0 : $toItemDepth - 1;
            if ($toItemDepth > $phpWordPersistenceRuns) {
                $phpWordPersistenceRuns = $toItemDepth;
            }

            if ((($mapItemType === MapItemType::TABLE) || ($mapItemType === MapItemType::TABLE_ROW)) === false) {
                $mapItem = $this->createMapItem(
                    fromItem: $fromItem,
                    toItem: $this->buildOutputMapToItem(
                        toItem: $toItem,
                        mapItemType: $mapItemType,
                        phpWordPersistenceRun: $phpWordRun,
                    ),
                    fromItemWithDotNotation: $fromItemWithDotNotation,
                    toItemWithDotNotation: $toItemWithDotNotation,
                    mapItemType: $mapItemType,
                    isArray: true,
                    depth: $toItemDepth,
                );

                $mapItems[] = $mapItem;

                continue;
            }

            $toItemSegments = $this->splitItemIntoSegments(itemWithDotNotation: $toItemWithDotNotation);
            $toItemFirstColumn = $this->firstItemSegment(
                itemSegmentKeys: $toItemSegments['keys'],
                allToItems: $allToItems,
            );
            $toItemReplaced = $this->replaceTableNameWithFirstColumn(
                item: $toItem,
                column: $toItemFirstColumn,
                itemSegments: $toItemSegments['segments'],
            );

            for ($j = 0; $j < $phpWordRun; ++$j) {
                $mapItem = $this->createMapItem(
                    fromItem: sprintf('[internal][nestedRows]%1$s', $fromItem),
                    toItem: $this->buildOutputMapToItem(
                        toItem: $toItemReplaced,
                        mapItemType: MapItemType::BLOCK_ITEM,
                        phpWordPersistenceRun: $j,
                    ),
                    fromItemWithDotNotation: $fromItemWithDotNotation,
                    toItemWithDotNotation: $toItemWithDotNotation,
                    mapItemType: MapItemType::BLOCK_ITEM,
                    firstTableColumn: $toItemFirstColumn,
                    isArray: true,
                    depth: $toItemDepth,
                );

                $internalNestedRowMapItems[] = $mapItem;
            }

            $mapItem = $this->createMapItem(
                fromItem: $fromItem,
                toItem: $this->buildOutputMapToItem(
                    toItem: $toItemReplaced,
                    mapItemType: $mapItemType,
                    phpWordPersistenceRun: $phpWordRun,
                ),
                fromItemWithDotNotation: $fromItemWithDotNotation,
                toItemWithDotNotation: $toItemWithDotNotation,
                mapItemType: $mapItemType,
                firstTableColumn: $toItemFirstColumn,
                isArray: true,
                depth: $toItemDepth,
            );

            $mapItems[] = $mapItem;
        }

        for ($i = 0; $i < $phpWordPersistenceRuns; ++$i) {
            $mapItem = $this->createMapItem(
                fromItem: sprintf('[internal][emptyFields][%1$s][values]', $i),
                toItem: sprintf('[%1$s][values]', $i),
                fromItemWithDotNotation: '[internal][emptyFields][*][values]',
                toItemWithDotNotation: '[*][values]',
                mapItemType: MapItemType::VALUE,
            );

            $internalEmptyFieldsMapItems[] = $mapItem;

            $mapItem = $this->createMapItem(
                fromItem: sprintf('[internal][emptyFields][%1$s][blocks]', $i),
                toItem: sprintf('[%1$s][blocks]', $i),
                fromItemWithDotNotation: '[internal][emptyFields][*][blocks]',
                toItemWithDotNotation: '[*][blocks]',
                mapItemType: MapItemType::VALUE,
            );

            $internalEmptyFieldsMapItems[] = $mapItem;

            $mapItem = $this->createMapItem(
                fromItem: sprintf('[internal][emptyFields][%1$s][rows]', $i),
                toItem: sprintf('[%1$s][rows]', $i),
                fromItemWithDotNotation: '[internal][emptyFields][*][rows]',
                toItemWithDotNotation: '[*][rows]',
                mapItemType: MapItemType::VALUE,
            );

            $internalEmptyFieldsMapItems[] = $mapItem;
        }

        $mapItems = array_merge($internalEmptyFieldsMapItems, $internalNestedRowMapItems, $mapItems);

        return new MapItems(items: $mapItems);
    }

    private function hasItemDotNotation(string $itemWithDotNotation): bool
    {
        return str_contains(haystack: $itemWithDotNotation, needle: PropertyAccessStrategy::WILDCARD);
    }

    /**
     * @throws UnknownMapItemTypeReceivedException when settings->types has an unknown type
     * @throws UnableToIdentifyDefaultMapItemTypeException when we can not fall back to default because it's not defined
     */
    private function createMapItemType(string $toItemWithDotNotation, array $settingTypes): MapItemType
    {
        if (\array_key_exists(key: $toItemWithDotNotation, array: $settingTypes)) {
            $settingMapItemType = $settingTypes[$toItemWithDotNotation];
            $mapItemType = MapItemType::tryFrom(value: $settingTypes[$toItemWithDotNotation]);

            if ($mapItemType === null) {
                throw new UnknownMapItemTypeReceivedException(
                    toItem: $toItemWithDotNotation,
                    unknownMapItemType: $settingMapItemType,
                    knownMapItemTypes: array_column(MapItemType::cases(), 'value'),
                    settingTypes: $settingTypes,
                );
            }

            return $mapItemType;
        }

        if (\array_key_exists(key: 'default', array: $settingTypes) === false) {
            throw new UnableToIdentifyDefaultMapItemTypeException(
                toItem: $toItemWithDotNotation,
                settingTypes: $settingTypes,
            );
        }

        $mapItemType = MapItemType::tryFrom(value: $settingTypes['default']);
        if ($mapItemType === null) {
            throw new UnknownMapItemTypeReceivedException(
                toItem: 'default',
                unknownMapItemType: $settingTypes['default'],
                knownMapItemTypes: array_column(MapItemType::cases(), 'value'),
                settingTypes: $settingTypes,
            );
        }

        return $mapItemType;
    }

    private function splitItemIntoSegments(string $itemWithDotNotation): array
    {
        $toItemSegmentsWithoutColumn = [];
        preg_match_all(
            pattern: self::REGEX_SPLIT_INTO_SEGMENTS,
            subject: $itemWithDotNotation,
            matches: $toItemSegmentsWithoutColumn,
            flags: \PREG_PATTERN_ORDER | \PREG_UNMATCHED_AS_NULL,
        );

        return [
            'keys' => $toItemSegmentsWithoutColumn[0],
            'segments' => $toItemSegmentsWithoutColumn[1],
        ];
    }

    private function replaceTableNameWithFirstColumn(string $item, string $column, array $itemSegments): string
    {
        $firstColumn = \count($itemSegments) - 1;

        $replace = $itemSegments[$firstColumn];

        return str_replace(search: $replace, replace: $column, subject: $item);
    }

    /**
     * @throws UnableToFindRelatedSegmentsException when we can't find any deeper nested items
     * @throws UnableToIdentifyLastSegmentKeyException when we can't identify the first column of a table (e.g. when table_row is mapped as an array)
     */
    private function firstItemSegment(array $itemSegmentKeys, array $allToItems): string
    {
        // break map item into segments and cut out columns
        $needle = implode('', $itemSegmentKeys);

        // find the first column in toItems
        $toItemRelatedColumns = array_filter(
            array: $allToItems,
            callback: function (string $allToItem) use ($needle): bool {
                if (str_contains(haystack: $allToItem, needle: $needle) === false) {
                    return false;
                }

                $column = str_replace(search: $needle, replace: '', subject: $allToItem);

                if (empty($column)) {
                    return false;
                }

                return true;
            },
        );

        if (empty($toItemRelatedColumns)) {
            throw new UnableToFindRelatedSegmentsException(
                needle: $needle,
                toItems: $allToItems,
            );
        }

        $firstToItem = $toItemRelatedColumns[array_key_first($toItemRelatedColumns)];
        $columnMatches = [];
        preg_match_all(
            pattern: self::REGEX_FIND_LAST_SEGMENT_VALUE,
            subject: $firstToItem,
            matches: $columnMatches,
            flags: \PREG_PATTERN_ORDER | \PREG_UNMATCHED_AS_NULL,
        );

        if (isset($columnMatches[1][0]) === false) {
            throw new UnableToIdentifyLastSegmentKeyException(
                needle: $firstToItem,
                toItems: $allToItems,
                relatedToItems: $toItemRelatedColumns,
            );
        }

        return $columnMatches[1][0];
    }

    public function fromArray(array $mapItems): MapItems
    {
        Assert::allIsInstanceOf(value: $mapItems, class: MapItem::class);

        return new MapItems($mapItems);
    }

    private function createMapItem(
        string $fromItem,
        string $toItem,
        string $fromItemWithDotNotation,
        string $toItemWithDotNotation,
        MapItemType $mapItemType,
        string $firstTableColumn = '',
        bool $isArray = false,
        int $depth = 0,
    ): MapItem {
        return new MapItem(
            fromItem: $fromItem,
            fromItemWithDotNotation: $fromItemWithDotNotation,
            toItem: $toItem,
            toItemWithDotNotation: $toItemWithDotNotation,
            mapItemType: $mapItemType,
            depth: $depth,
            isArray: $isArray,
            firstTableColumn: $firstTableColumn,
        );
    }

    private function buildOutputMapToItem(
        string $toItem,
        MapItemType $mapItemType,
        int $phpWordPersistenceRun = 0,
    ): string {
        $type = match ($mapItemType) {
            MapItemType::VALUE => self::MAP_ITEM_TYPE_VALUES,
            MapItemType::BLOCK => self::MAP_ITEM_TYPE_BLOCKS,
            MapItemType::BLOCK_ITEM => self::MAP_ITEM_TYPE_BLOCKS,
            MapItemType::TABLE => self::MAP_ITEM_TYPE_ROWS,
            MapItemType::TABLE_ROW => self::MAP_ITEM_TYPE_ROWS,
            default => '',
        };

        return sprintf('[%1$s][%2$s]%3$s', $phpWordPersistenceRun, $type, $toItem);
    }
}
