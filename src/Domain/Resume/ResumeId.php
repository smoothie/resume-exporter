<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume;

class ResumeId implements \Stringable
{
    public function __construct(
        private readonly string $id,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
