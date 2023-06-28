<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

class MapItems implements \Countable
{
    public const TYPE_ARRAY = 'ARRAY';
    public const TYPE_NONE_ARRAY = 'NONE_ARRAY';
    public const TYPE_ALL = 'ALL';

    /**
     * @param MapItem[] $items
     */
    public function __construct(
        private readonly array $items,
    ) {
    }

    /**
     * @return MapItem[]
     */
    public function getArrayItems(): array
    {
        return array_filter($this->items, fn (MapItem $mapItem) => $mapItem->isArray());
    }

    public function getHighestDepth(): int
    {
        $highestDepth = 0;
        foreach ($this->items as $item) {
            if ($item->depth() <= $highestDepth) {
                continue;
            }

            $highestDepth = $item->depth();
        }

        return $highestDepth;
    }

    /**
     * @return MapItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return MapItem[]
     */
    public function getNoneArrayItems(): array
    {
        return array_filter($this->items, fn (MapItem $mapItem) => $mapItem->isArray() === false);
    }

    public function add(MapItem $mapItem): self
    {
        $new = array_merge([], $this->items);
        $new[] = $mapItem;

        return new self($new);
    }

    /**
     * @psalm-return int<0, max>
     */
    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * @return string[]
     *
     * @psalm-return array<string, string>
     */
    public function toArray(string $type = 'ALL'): array
    {
        $typeItems = match ($type) {
            self::TYPE_ARRAY => $this->getArrayItems(),
            self::TYPE_NONE_ARRAY => $this->getNoneArrayItems(),
            self::TYPE_ALL => $this->getItems(),
            default => throw new \Exception(sprintf('MapItems: Unable to filter unknown type: "%1$s"', $type)),
        };

        $items = [];
        foreach ($typeItems as $item) {
            $items[$item->fromItem()] = $item->toItem();
        }

        return $items;
    }
}
