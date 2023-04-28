<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Skills;

use Webmozart\Assert\Assert;

class Skill
{
    /**
     * @param DetailedKeyword[] $detailedKeywords
     */
    public function __construct(
        private readonly string $name,
        #[\JetBrains\PhpStorm\Deprecated(reason: 'Will be removed as soon as we support custom fields in JSONResume')] private readonly string $label,
        private readonly array $detailedKeywords,
    ) {
        Assert::allIsInstanceOf(value: $detailedKeywords, class: DetailedKeyword::class);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function detailedKeywords(): array
    {
        return $this->detailedKeywords;
    }
}
