<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToReplaceDotNotationException;

class MapItem
{
    public function __construct(
        private readonly string $fromItem,
        private readonly string $fromItemWithDotNotation,
        private readonly string $toItem,
        private readonly string $toItemWithDotNotation,
        private readonly int $depth,
        public readonly bool $isArray,
        private readonly int $count = 0,
        private readonly string $firstTableColumn = '',
    ) {
    }

    public function fromItem(): string
    {
        return $this->fromItem;
    }

    public function toItem(): string
    {
        return $this->toItem;
    }

    public function depth(): int
    {
        return $this->depth;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    public function fromItemWithDotNotation(): string
    {
        return $this->fromItemWithDotNotation;
    }

    public function toItemWithDotNotation(): string
    {
        return $this->toItemWithDotNotation;
    }

    /**
     * @deprecated extract into a repository method
     */
    public function firstTableColumn(): string
    {
        return $this->firstTableColumn;
    }

    /**
     * @throws UnableToReplaceDotNotationException when unable to find needle in haystack
     */
    public function replaceInItems(string $needle, string $replace): self
    {
        $newToItem = $this->replaceFirstInString(haystack: $this->toItem, needle: $needle, replace: $replace);
        $newFromItem = $this->replaceFirstInString(haystack: $this->fromItem, needle: $needle, replace: $replace);

        $new = new self(
            fromItem: $newFromItem,
            fromItemWithDotNotation: $this->fromItemWithDotNotation,
            toItem: $newToItem,
            toItemWithDotNotation: $this->toItemWithDotNotation,
            depth: $this->depth,
            isArray: $this->isArray,
            count: $this->count,
            firstTableColumn: $this->firstTableColumn,
        );

        return clone $new;
    }

    /**
     * @throws UnableToReplaceDotNotationException when unable to find needle in haystack
     */
    private function replaceFirstInString(string $haystack, string $needle, string $replace): string
    {
        $position = strpos($haystack, $needle);
        if ($position === false) {
            throw new UnableToReplaceDotNotationException(haystack: $haystack, needle: $needle, replace: $replace);
        }

        return substr_replace(string: $haystack, replace: $replace, offset: $position, length: \strlen($needle));
    }

    public function setCount(int $count): self
    {
        return new self(
            fromItem: $this->fromItem,
            fromItemWithDotNotation: $this->fromItemWithDotNotation,
            toItem: $this->toItem,
            toItemWithDotNotation: $this->toItemWithDotNotation,
            depth: $this->depth,
            isArray: $this->isArray,
            count: $count,
            firstTableColumn: $this->firstTableColumn,
        );
    }
}
