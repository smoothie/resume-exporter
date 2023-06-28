<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

class DomPdfFont
{
    public const KEY_FAMILY = 'family';
    public const KEY_STYLE = 'style';
    public const KEY_WEIGHT = 'weight';
    public const KEY_FILE = 'fontFile';
    public const KEY_FONTS = 'fonts';

    public function __construct(
        private readonly string $family,
        private readonly string $style,
        private readonly string $weight,
        private readonly string $fontFile,
    ) {
    }

    /**
     * @return string[]
     *
     * @psalm-return array{family: string, style: string, weight: string}
     */
    public function getStyle(): array
    {
        return [self::KEY_FAMILY => $this->family, self::KEY_STYLE => $this->style, self::KEY_WEIGHT => $this->weight];
    }

    public function getRemoteFile(): string
    {
        return $this->fontFile;
    }
}
