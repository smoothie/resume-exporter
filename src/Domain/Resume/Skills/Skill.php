<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Skills;

use JetBrains\PhpStorm\Deprecated;
use Webmozart\Assert\Assert;

class Skill
{
    /**
     * @param DetailedKeyword[] $detailedKeywords
     */
    public function __construct(
        private readonly string $name,
        #[Deprecated(reason: 'Will be removed as soon as we support custom fields in JSONResume')] private readonly string $label,
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

    /**
     * @return DetailedKeyword[]
     *
     * @psalm-return array<DetailedKeyword>
     */
    public function detailedKeywords(): array
    {
        return $this->detailedKeywords;
    }
}
