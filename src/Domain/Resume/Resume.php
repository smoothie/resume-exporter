<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume;

use Smoothie\ResumeExporter\Domain\Resume\Basics\Basic;
use Smoothie\ResumeExporter\Domain\Resume\Educations\Educations;
use Smoothie\ResumeExporter\Domain\Resume\Languages\Languages;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Meta;
use Smoothie\ResumeExporter\Domain\Resume\Projects\Projects;
use Smoothie\ResumeExporter\Domain\Resume\Skills\Skills;

class Resume
{
    public function __construct(
        private readonly ResumeId $id,
        private readonly Basic $basic,
        private readonly Educations $educations,
        private readonly Languages $languages,
        private readonly Skills $skills,
        private readonly Projects $projects,
        private readonly Meta $meta,
    ) {
    }

    public function resumeId(): ResumeId
    {
        return $this->id;
    }

    public function basic(): Basic
    {
        return $this->basic;
    }

    public function educations(): array
    {
        return $this->educations->educations();
    }

    public function languages(): array
    {
        return $this->languages->languages();
    }

    public function skills(): array
    {
        return $this->skills->skills();
    }

    public function projects(): array
    {
        return $this->projects->projects();
    }

    public function meta(): Meta
    {
        return $this->meta;
    }

    public function toArray(): array
    {
        $resume = clone $this;

        $basic = $resume->basic();
        $basicLocation = $basic->location();
        $basicProfiles = $basic->profiles();
        $basicOverview = $basic->overview();

        $profiles = [];
        foreach ($basicProfiles as $basicProfile) {
            $profiles[] = [
                'Network' => $basicProfile->network(),
                'Url' => $basicProfile->url(),
                'Username' => $basicProfile->username(),
            ];
        }

        $overviewItems = [];
        foreach ($basicOverview->items() as $basicOverviewItem) {
            $overviewItems[] = [
                'Label' => $basicOverviewItem->label(),
                'Value' => $basicOverviewItem->value(),
            ];
        }

        $educations = [];
        foreach ($resume->educations() as $education) {
            $educations[] = [
                'Area' => $education->area(),
                'EndDate' => $education->endDate(),
                'StartDate' => $education->startDate(),
                'StudyType' => $education->studyType(),
            ];
        }

        $languages = [];
        foreach ($resume->languages() as $language) {
            $languages[] = [
                'Language' => $language->language(),
                'Fluency' => $language->fluency(),
            ];
        }

        $projects = [];
        foreach ($resume->projects() as $project) {
            $highlights = [];
            foreach ($project->highlights() as $highlight) {
                $highlights[] = $highlight->highlight();
            }
            $keywords = [];
            foreach ($project->keywords() as $keyword) {
                $keywords[] = $keyword->keyword();
            }
            $roles = [];
            foreach ($project->roles() as $role) {
                $roles[] = $role->role();
            }

            $projects[] = [
                'Name' => $project->name(),
                'Description' => $project->description(),
                'Entity' => $project->entity(),
                'Type' => $project->type(),
                'StartDate' => $project->startDate(),
                'EndDate' => $project->endDate(),
                'Highlights' => $highlights,
                'Keywords' => $keywords,
                'Roles' => $roles,
            ];
        }

        $skills = [];
        foreach ($resume->skills() as $skill) {
            $detailedKeywords = [];
            foreach ($skill->detailedKeywords() as $detailedKeyword) {
                $detailedKeywords[] = [
                    'Keyword' => $detailedKeyword->keyword(),
                    'Level' => $detailedKeyword->level(),
                    'ExperienceInYears' => $detailedKeyword->experienceInYears(),
                ];
            }
            $skills[] = [
                'Name' => $skill->name(),
                'Label' => $skill->label(),
                'DetailedKeywords' => $detailedKeywords,
            ];
        }

        $meta = $resume->meta();
        $metaContentLabels = $meta->content()->labels();
        $metaContentLabelsYears = $metaContentLabels->years();
        $id = $resume->resumeId();

        return [
            'Basic' => [
                'Email' => $basic->email(),
                'Label' => $basic->label(),
                'Name' => $basic->name(),
                'Phone' => $basic->phone(),
                'Summary' => $basic->summary(),
                'Url' => $basic->url(),
                'Location' => [
                    'Address' => $basicLocation->address(),
                    'City' => $basicLocation->city(),
                    'CountryCode' => $basicLocation->countryCode(),
                    'PostalCode' => $basicLocation->postalCode(),
                ],
                'Profiles' => $profiles,
                'Overview' => [
                    'Items' => $overviewItems,
                ],
            ],
            'Education' => $educations,
            'Languages' => $languages,
            'Projects' => $projects,
            'Skills' => $skills,
            'Meta' => [
                'Canonical' => $meta->canonical(),
                'Version' => $meta->version(),
                'LastModified' => $meta->lastModified(),
                'Internal' => [
                    'ResumeId' => $id->getId(),
                ],
                'Content' => [
                    'Labels' => [
                        'Skills' => $metaContentLabels->skills(),
                        'Languages' => $metaContentLabels->languages(),
                        'Language' => $metaContentLabels->language(),
                        'Overview' => $metaContentLabels->overview(),
                        'Projects' => $metaContentLabels->projects(),
                        'Education' => $metaContentLabels->education(),
                        'Competences' => $metaContentLabels->competences(),
                        'MoreCompetences' => $metaContentLabels->moreCompetences(),
                        'ExperienceInYears' => $metaContentLabels->experienceInYears(),
                        'ExperienceLevel' => $metaContentLabels->experienceLevel(),
                        'Page' => $metaContentLabels->page(),
                        'PageOf' => $metaContentLabels->pageOf(),
                        'Years' => [
                            'Singular' => $metaContentLabelsYears->singular(),
                            'Plural' => $metaContentLabelsYears->plural(),
                        ],
                    ],
                ],
            ],
        ];
    }
}
