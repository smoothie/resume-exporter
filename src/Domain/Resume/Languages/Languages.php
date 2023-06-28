<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Languages;

use Webmozart\Assert\Assert;

class Languages
{
    /**
     * @param Language[] $languages
     */
    public function __construct(
        private readonly array $languages,
    ) {
        Assert::allIsInstanceOf(value: $languages, class: Language::class);
    }

    /**
     * @return Language[]
     *
     * @psalm-return array<Language>
     */
    public function languages(): array
    {
        return $this->languages;
    }
}
