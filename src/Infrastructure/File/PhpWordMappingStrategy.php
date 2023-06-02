<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class PhpWordMappingStrategy implements MappingStrategy
{
    public function __construct(
        private readonly MappingStrategy $mappingStrategy,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    public function translate(array $map, array $from, array $settings): array
    {
        $internalMap = [];
        foreach ($map as $fromMapItem => $toMapItem) {
            if (str_contains(haystack: $fromMapItem, needle: '[internal]') === false) {
                continue;
            }

            $internalMap[$fromMapItem] = $toMapItem;
        }

        $from['internal'] = [];
        foreach ($internalMap as $fromMapItem => $toMapItem) {
            if (str_contains(haystack: $fromMapItem, needle: '[nestedRows]') === true) {
                // todo set the placeholder
                $lastColumnMatches = [];
                preg_match_all(
                    pattern: PhpWordMapItemsFactory::REGEX_FIND_LAST_SEGMENT_VALUE,
                    subject: $toMapItem,
                    matches: $lastColumnMatches,
                    flags: \PREG_PATTERN_ORDER | \PREG_UNMATCHED_AS_NULL,
                );

                $this->propertyAccessor->setValue(
                    objectOrArray: $from,
                    propertyPath: $fromMapItem,
                    value: $lastColumnMatches[1][0],
                );

                continue;
            }

            if (str_contains(haystack: $fromMapItem, needle: '[emptyFields]') === true) {
                $this->propertyAccessor->setValue(objectOrArray: $from, propertyPath: $fromMapItem, value: []);

                continue;
            }
        }

        return $this->mappingStrategy->translate(map: $map, from: $from, settings: $settings);
    }

    public function normalize(array $map, array $from, array $settings): array
    {
        $mapItemsFactory = new PhpWordMapItemsFactory();

        $normalizedMap = $this->mappingStrategy->normalize(map: $map, from: $from, settings: $settings);

        $mapItems = $mapItemsFactory->createMapItems(map: $normalizedMap, settings: $settings);

        return $mapItems->toArray();
    }

    public function validate(array $map, array $from, array $settings): void
    {
        // todo
        // -> settings: throw hard when no default and not all map items have a type setting
        // -> settings: throw hard when a settings type is unknown
        // -> settings: throw hard when a block not ends with [*]
        // -> settings: throw hard when a table not ends with [*]
        // -> settings: throw hard when a value ends with [*]
        // -> settings: throw hard when a block item ends with [*]
        // -> settings: throw hard when a table_row ends with [*]
        $this->mappingStrategy->validate(map: $map, from: $from, settings: $settings);
    }
}
