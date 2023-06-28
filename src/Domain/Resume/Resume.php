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

    /**
     * @return ((((mixed|string)[]|mixed|string)[]|mixed|string)[]|string)[][]
     *
     * @psalm-return array{Basic: array{Email: string, Label: string, Name: string, Phone: string, Summary: string, Url: string, Location: array{Address: string, City: string, CountryCode: string, PostalCode: string}, Profiles: list{0?: array{Network: mixed, Url: mixed, Username: mixed},...}, Overview: array{Items: list{0?: array{Label: mixed, Value: mixed},...}}}, Education: list{0?: array{Area: mixed, EndDate: mixed, StartDate: mixed, StudyType: mixed},...}, Languages: list{0?: array{Language: mixed, Fluency: mixed},...}, Projects: list{0?: array{Name: mixed, Description: mixed, Entity: mixed, Type: mixed, StartDate: mixed, EndDate: mixed, Highlights: list{0?: mixed,...}, Keywords: list{0?: mixed,...}, Roles: list{0?: mixed,...}},...}, Skills: list{0?: array{Name: mixed, Label: mixed, DetailedKeywords: list{0?: array{Keyword: mixed, Level: mixed, ExperienceInYears: mixed},...}},...}, Meta: array{Canonical: string, Version: string, LastModified: string, Internal: array{ResumeId: string}, Content: array{Labels: array{Skills: string, Languages: string, Language: string, Overview: string, Projects: string, Education: string, Competences: string, MoreCompetences: string, ExperienceInYears: string, ExperienceLevel: string, Page: string, PageOf: string, Years: array{Singular: string, Plural: string}}}}}
     */
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
