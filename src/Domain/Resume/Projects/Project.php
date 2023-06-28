<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Projects;

use Webmozart\Assert\Assert;

class Project
{
    /**
     * @param Highlight[] $highlights
     * @param Keyword[] $keywords
     * @param Role[] $roles
     */
    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly string $entity,
        private readonly string $type,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly array $highlights,
        private readonly array $keywords,
        private readonly array $roles,
    ) {
        Assert::allIsInstanceOf(value: $highlights, class: Highlight::class);
        Assert::allIsInstanceOf(value: $keywords, class: Keyword::class);
        Assert::allIsInstanceOf(value: $roles, class: Role::class);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function entity(): string
    {
        return $this->entity;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function startDate(): string
    {
        return $this->startDate;
    }

    public function endDate(): string
    {
        return $this->endDate;
    }

    /**
     * @return Highlight[]
     *
     * @psalm-return array<Highlight>
     */
    public function highlights(): array
    {
        return $this->highlights;
    }

    /**
     * @return Keyword[]
     *
     * @psalm-return array<Keyword>
     */
    public function keywords(): array
    {
        return $this->keywords;
    }

    /**
     * @return Role[]
     *
     * @psalm-return array<Role>
     */
    public function roles(): array
    {
        return $this->roles;
    }
}
