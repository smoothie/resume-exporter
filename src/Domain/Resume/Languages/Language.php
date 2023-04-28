<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Languages;

class Language
{
    public function __construct(private readonly string $language, private readonly string $fluency)
    {
    }

    public function language(): string
    {
        return $this->language;
    }

    public function fluency(): string
    {
        return $this->fluency;
    }
}
