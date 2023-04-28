<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Meta;

use Smoothie\ResumeExporter\Domain\Resume\Meta\Content\Content;

class Meta
{
    public function __construct(
        private readonly string $canonical,
        private readonly string $version,
        private readonly string $lastModified,
        private readonly Content $content,
    ) {
    }

    public function canonical(): string
    {
        return $this->canonical;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function lastModified(): string
    {
        return $this->lastModified;
    }

    public function content(): Content
    {
        return $this->content;
    }
}
