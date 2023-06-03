<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Mapping;

use Psr\Log\LoggerInterface;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\InvalidParentFromItemFormatException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\MismatchedMapItemDepthException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToFindParentFromItemException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToReplaceDotNotationException;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemRepository;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemsFactory;
use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Smoothie\ResumeExporter\Infrastructure\Mapping\Exceptions\UnableToMapException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

class PropertyAccessStrategy implements MappingStrategy
{
    public const WILDCARD = '[*]';
    public const ASTERISK = '*';

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly LoggerInterface $logger,
        private readonly MapItemsFactory $propertyAccessMapItemsFactory,
        private readonly MapItemRepository $mapItemRepository,
    ) {
    }

    public function translate(array $map, array $from, array $settings): array
    {
        $to = [];

        foreach ($map as $fromItem => $toItem) {
            $fromValue = $this->propertyAccessor->getValue(objectOrArray: $from, propertyPath: $fromItem);
            $this->propertyAccessor->setValue(objectOrArray: $to, propertyPath: $toItem, value: $fromValue);
        }

        // This block is probably never possible but just in case underlying accessor changes happen
        if (\is_array($to) === false) {
            $this->logger->error(
                message: 'PropertyAccessStrategy: Unexpected error. For unknown reason $to is not an array.',
                context: [
                    'to' => $to,
                    'map' => $map,
                    'from' => $from,
                ],
            );
            // todo: cleanup throw some exception
        }

        Assert::isArray(
            value: $to,
            message: 'PropertyAccessStrategy: Unexpected error. For unknown reason $to is not an array.',
        );

        return $to;
    }

    /**
     * @throws InvalidParentFromItemFormatException
     * @throws MismatchedMapItemDepthException
     * @throws UnableToFindParentFromItemException
     * @throws UnableToReplaceDotNotationException
     */
    public function normalize(array $map, array $from, array $settings): array
    {
        $mapItems = $this->propertyAccessMapItemsFactory->createMapItems(map: $map);
        $mapArrayItems = $mapItems->getArrayItems();
        $highestDepth = $mapItems->getHighestDepth();

        if (empty($mapArrayItems)) {
            return $map;
        }

        $noneArrayItems = $this->propertyAccessMapItemsFactory->fromArray($mapItems->getNoneArrayItems());
        $unNormalizedArrayItems = $this->propertyAccessMapItemsFactory->fromArray([]);
        $arrayItems = $this->propertyAccessMapItemsFactory->fromArray([]);

        foreach ($mapArrayItems as $mapItem) {
            $countItems = $this->mapItemRepository->findCountOfParentFromItem(mapItem: $mapItem, from: $from);
            $unNormalizedArrayItems = $unNormalizedArrayItems->add($mapItem->setCount(count: $countItems));
        }

        foreach ($unNormalizedArrayItems->getItems() as $mapItem) {
            $mapItemCount = $mapItem->getCount();

            for ($j = 0; $j < $mapItemCount; ++$j) {
                // TODO: cleanup.. Extract Asterisk notation into domain space
                $normalizedMapItem = $mapItem->replaceInItems(needle: self::ASTERISK, replace: (string) $j);
                $arrayItems = $arrayItems->add($normalizedMapItem);
            }
        }

        $normalizedMap = array_merge($noneArrayItems->toArray(), $arrayItems->toArray());
        if ($highestDepth <= 1) {
            return $normalizedMap;
        }

        // TODO: cleanup.. maybe extract everything above to somewhere else and use a loop on highestDepth
        return $this->normalize(map: $normalizedMap, from: $from, settings: $settings);
    }

    /**
     * @throws UnableToMapException when we can't find a map item, or a map item is not a string, $map contains array notation
     * @throws InvalidArgumentException when $map or $from are empty
     */
    public function validate(array $map, array $from, array $settings): void
    {
        Assert::allNotEmpty([
            $map,
            $from,
        ], 'PropertyAccessStrategy: $map and $from must not be empty.');

        $unreadableItems = [];
        $invalidMapItems = [];
        $invalidArrayItems = [];

        foreach ($map as $fromItem => $toItem) {
            if (\is_string($toItem) === false || \is_string($fromItem) === false) {
                $invalidMapItems[$fromItem] = $toItem;
                $this->logger->error(
                    message: 'PropertyAccessStrategy: Invalid map item, $map must be flat and key/values must be strings.',
                    context: [
                        'toItem' => $toItem,
                        'fromItem' => $fromItem,
                        'from' => $from,
                        'map' => $map,
                    ],
                );

                continue;
            }

            if (str_contains(haystack: $toItem, needle: self::WILDCARD)
                && str_contains(haystack: $fromItem, needle: self::WILDCARD)) {
                // not normalized map and found an array notation

                if (mb_substr_count(haystack: $toItem, needle: self::WILDCARD) === mb_substr_count(
                    haystack: $fromItem,
                    needle: self::WILDCARD,
                )) {
                    // same amount of wildcards -> considered valid
                    continue;
                }

                $invalidArrayItems[$fromItem] = $toItem;
                $this->logger->error(
                    message: 'PropertyAccessStrategy: Invalid array item found.',
                    context: [
                        'fromItem' => $fromItem,
                        'toItem' => $toItem,
                        'from' => $from,
                        'map' => $map,
                    ],
                );

                continue;
            }

            if ($this->propertyAccessor->isReadable(objectOrArray: $from, propertyPath: $fromItem)) {
                continue;
            }

            $unreadableItems[$fromItem] = $toItem;
            $this->logger->error(
                message: 'PropertyAccessStrategy: Unable to find item in $map.',
                context: [
                    'fromItem' => $fromItem,
                    'from' => $from,
                    'map' => $map,
                ],
            );
        }

        if (empty($unreadableItems) && empty($invalidMapItems) && empty($invalidArrayItems)) {
            return;
        }

        throw new UnableToMapException(
            invalidItems: $invalidMapItems,
            unreadableItems: $unreadableItems,
            invalidArrayItems: $invalidArrayItems,
            from: $from,
            map: $map,
        );
    }
}
