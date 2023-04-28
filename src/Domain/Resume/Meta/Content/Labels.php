<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Meta\Content;

/**
 * @private
 *
 * @deprecated Will be removed as soon as we support custom fields in JSONResume
 */
class Labels
{
    public function __construct(
        private readonly string $skills,
        private readonly string $languages,
        private readonly string $language,
        private readonly string $overview,
        private readonly string $projects,
        private readonly string $education,
        private readonly string $competences,
        private readonly string $moreCompetences,
        private readonly string $experienceInYears,
        private readonly string $page,
        private readonly string $pageOf,
        private readonly Years $years,
    ) {
    }

    public function skills(): string
    {
        return $this->skills;
    }

    public function languages(): string
    {
        return $this->languages;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function overview(): string
    {
        return $this->overview;
    }

    public function projects(): string
    {
        return $this->projects;
    }

    public function education(): string
    {
        return $this->education;
    }

    public function competences(): string
    {
        return $this->competences;
    }

    public function moreCompetences(): string
    {
        return $this->moreCompetences;
    }

    public function experienceInYears(): string
    {
        return $this->experienceInYears;
    }

    public function page(): string
    {
        return $this->page;
    }

    public function pageOf(): string
    {
        return $this->pageOf;
    }

    public function years(): Years
    {
        return $this->years;
    }
}
