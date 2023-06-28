<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Educations;

use Webmozart\Assert\Assert;

class Educations
{
    /**
     * @param Education[] $educations
     */
    public function __construct(
        private readonly array $educations,
    ) {
        Assert::allIsInstanceOf(value: $educations, class: Education::class);
    }

    /**
     * @return Education[]
     *
     * @psalm-return array<Education>
     */
    public function educations(): array
    {
        return $this->educations;
    }
}
