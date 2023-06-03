<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

class DomPdfFont
{
    public function __construct(
        private readonly string $family,
        private readonly string $style,
        private readonly string $weight,
        private readonly string $fontFile,
    ) {
    }

    public function getStyle(): array
    {
        return ['family' => $this->family, 'style' => $this->style, 'weight' => $this->weight];
    }

    public function getRemoteFile(): string
    {
        return $this->fontFile;
    }
}
