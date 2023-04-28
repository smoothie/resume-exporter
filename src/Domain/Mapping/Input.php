<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Webmozart\Assert\Assert;

class Input
{
    public function __construct(
        private string $inputId,
        private string $inputSource,
        private string $mapSource,
        private array $input,
        private array $map,
    ) {
        Assert::uniqueValues(values: $map);
    }

    public function getInputId(): string
    {
        return $this->inputId;
    }

    public function getInputSource(): string
    {
        return $this->inputSource;
    }

    public function getMapSource(): string
    {
        return $this->mapSource;
    }

    public function getInput(): array
    {
        return $this->input;
    }

    public function getMap(): array
    {
        return $this->map;
    }
}
