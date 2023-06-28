<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Domain\Resume\Basics\Basic;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Locations\Location;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews\Item;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews\Overview;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles\Profile;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles\Profiles;
use Smoothie\ResumeExporter\Domain\Resume\Educations\Education;
use Smoothie\ResumeExporter\Domain\Resume\Educations\Educations;
use Smoothie\ResumeExporter\Domain\Resume\Exceptions\InvalidCanonicalReceivedException;
use Smoothie\ResumeExporter\Domain\Resume\Languages\Language;
use Smoothie\ResumeExporter\Domain\Resume\Languages\Languages;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Content\Content;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Content\Labels;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Content\Years;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Meta;
use Smoothie\ResumeExporter\Domain\Resume\Projects\Highlight;
use Smoothie\ResumeExporter\Domain\Resume\Projects\Keyword;
use Smoothie\ResumeExporter\Domain\Resume\Projects\Project;
use Smoothie\ResumeExporter\Domain\Resume\Projects\Projects;
use Smoothie\ResumeExporter\Domain\Resume\Projects\Role;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Domain\Resume\ResumeFactory as ReadResumeFactory;
use Smoothie\ResumeExporter\Domain\Resume\ResumeId;
use Smoothie\ResumeExporter\Domain\Resume\Skills\DetailedKeyword;
use Smoothie\ResumeExporter\Domain\Resume\Skills\Skill;
use Smoothie\ResumeExporter\Domain\Resume\Skills\Skills;
use Smoothie\ResumeExporter\Infrastructure\Constraints\StringExists;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class JsonResumeFactory implements ReadResumeFactory
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function fromArray(array $canonicalData): Resume
    {
        $resumeId = new ResumeId($canonicalData['Meta']['Internal']['ResumeId']);
        $basic = $this->buildBasic($canonicalData);
        $educations = $this->buildEducations($canonicalData);
        $languages = $this->buildLanguages($canonicalData);
        $skills = $this->buildSkills($canonicalData);
        $projects = $this->buildProjects($canonicalData);
        $meta = $this->buildMeta($canonicalData);

        return new Resume(
            id: $resumeId,
            basic: $basic,
            educations: $educations,
            languages: $languages,
            skills: $skills,
            projects: $projects,
            meta: $meta,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $canonicalData): void
    {
        $groups = new Assert\GroupSequence(['Default']);
        $constraints = new Assert\Collection(
            fields: [
                'Basic' => new Assert\Collection(
                    fields: [
                        'Email' => [new StringExists()],
                        'Label' => [new StringExists()],
                        'Name' => [new StringExists()],
                        'Phone' => [new StringExists()],
                        'Summary' => [new StringExists()],
                        'Url' => [new StringExists()],
                        'Location' => new Assert\Collection([
                            'Address' => [new StringExists()],
                            'City' => [new StringExists()],
                            'CountryCode' => [new StringExists()],
                            'PostalCode' => [new StringExists()],
                        ]),
                        'Profiles' => new Assert\All([
                            new Assert\Collection([
                                'Network' => [new StringExists()],
                                'Url' => [new StringExists()],
                                'Username' => [new StringExists()],
                            ]),
                        ]),
                        'Overview' => new Assert\Collection([
                            'Items' => new Assert\All([
                                new Assert\Collection([
                                    'Label' => [new StringExists()],
                                    'Value' => [new StringExists()],
                                ]),
                            ]),
                        ]),
                    ],
                ),
                'Education' => new Assert\All([
                    new Assert\Collection([
                        'Area' => [new StringExists()],
                        'EndDate' => [new StringExists()],
                        'StartDate' => [new StringExists()],
                        'StudyType' => [new StringExists()],
                    ]),
                ]),
                'Languages' => new Assert\All([
                    new Assert\Collection([
                        'Language' => [new StringExists()],
                        'Fluency' => [new StringExists()],
                    ]),
                ]),
                'Projects' => new Assert\All([
                    new Assert\Collection([
                        'Name' => [new StringExists()],
                        'Description' => [new StringExists()],
                        'Entity' => [new StringExists()],
                        'Type' => [new StringExists()],
                        'StartDate' => [new StringExists()],
                        'EndDate' => [new StringExists()],
                        'Highlights' => new Assert\All([new StringExists()]),
                        'Keywords' => new Assert\All([new StringExists()]),
                        'Roles' => new Assert\All([new StringExists()]),
                    ]),
                ]),
                'Skills' => new Assert\All([
                    new Assert\Collection([
                        'Name' => [new StringExists()],
                        'Label' => [new StringExists()],
                        'DetailedKeywords' => new Assert\All([
                            new Assert\Collection([
                                'Keyword' => [new StringExists()],
                                'Level' => [new StringExists()],
                                'ExperienceInYears' => [new StringExists()],
                            ]),
                        ]),
                    ]),
                ]),
                'Meta' => new Assert\Collection([
                    'Canonical' => [new StringExists()],
                    'Version' => [new StringExists()],
                    'LastModified' => [new StringExists()],
                    'Internal' => new Assert\Collection([
                        'ResumeId' => [new StringExists()],
                    ]),
                    'Content' => new Assert\Collection([
                        'Labels' => new Assert\Collection([
                            'Skills' => [new StringExists()],
                            'Languages' => [new StringExists()],
                            'Language' => [new StringExists()],
                            'Overview' => [new StringExists()],
                            'Projects' => [new StringExists()],
                            'Education' => [new StringExists()],
                            'Competences' => [new StringExists()],
                            'MoreCompetences' => [new StringExists()],
                            'ExperienceInYears' => [new StringExists()],
                            'ExperienceLevel' => [new StringExists()],
                            'Page' => [new StringExists()],
                            'PageOf' => [new StringExists()],
                            'Years' => new Assert\Collection([
                                'Singular' => [new StringExists()],
                                'Plural' => [new StringExists()],
                            ]),
                        ]),
                    ]),
                ]),
            ],
            allowExtraFields: false,
            allowMissingFields: false,
        );

        $violations = $this->validator->validate(value: $canonicalData, constraints: $constraints, groups: $groups);
        if ($violations->count() === 0) {
            return;
        }

        $violationAsArray = [];

        foreach ($violations as $constraint) {
            $propertyPath = $constraint->getPropertyPath();
            $violationAsArray[$propertyPath][] = $constraint->getMessage();
        }

        throw new InvalidCanonicalReceivedException(violations: $violationAsArray, input: $canonicalData);
    }

    private function buildBasic(array $canonical): Basic
    {
        return new Basic(
            email: $canonical['Basic']['Email'],
            label: $canonical['Basic']['Label'],
            name: $canonical['Basic']['Name'],
            phone: $canonical['Basic']['Phone'],
            summary: $canonical['Basic']['Summary'],
            url: $canonical['Basic']['Url'],
            location: $this->buildBasicLocation(canonical: $canonical),
            profiles: $this->buildBasicProfiles(canonical: $canonical),
            overview: $this->buildBasicOverview(canonical: $canonical),
        );
    }

    private function buildBasicLocation(array $canonical): Location
    {
        return new Location(
            address: $canonical['Basic']['Location']['Address'],
            city: $canonical['Basic']['Location']['City'],
            countryCode: $canonical['Basic']['Location']['CountryCode'],
            postalCode: $canonical['Basic']['Location']['PostalCode'],
        );
    }

    private function buildBasicProfiles(array $canonical): Profiles
    {
        $profiles = [];
        foreach ($canonical['Basic']['Profiles'] as $profile) {
            $profiles[] = $this->buildBasicProfile(profile: $profile);
        }

        return new Profiles(
            profiles: $profiles,
        );
    }

    private function buildBasicProfile(array $profile): Profile
    {
        return new Profile(
            network: $profile['Network'],
            url: $profile['Url'],
            username: $profile['Username'],
        );
    }

    private function buildBasicOverview(array $canonical): Overview
    {
        $items = [];
        foreach ($canonical['Basic']['Overview']['Items'] as $item) {
            $items[] = $this->buildBasicOverviewItem($item);
        }

        return new Overview(items: $items);
    }

    private function buildBasicOverviewItem(array $item): Item
    {
        return new Item(
            label: $item['Label'],
            value: $item['Value'],
        );
    }

    private function buildEducations(array $canonical): Educations
    {
        $educations = [];
        foreach ($canonical['Education'] as $education) {
            $educations[] = $this->buildEducation(education: $education);
        }

        return new Educations(
            educations: $educations,
        );
    }

    private function buildEducation(array $education): Education
    {
        return new Education(
            area: $education['Area'],
            endDate: $education['EndDate'],
            startDate: $education['StartDate'],
            studyType: $education['StudyType'],
        );
    }

    private function buildLanguages(array $canonical): Languages
    {
        $languages = [];
        foreach ($canonical['Languages'] as $language) {
            $languages[] = $this->buildLanguage(language: $language);
        }

        return new Languages(
            languages: $languages,
        );
    }

    private function buildLanguage(array $language): Language
    {
        return new Language(
            language: $language['Language'],
            fluency: $language['Fluency'],
        );
    }

    private function buildSkills(array $canonical): Skills
    {
        $skills = [];
        foreach ($canonical['Skills'] as $skill) {
            $skills[] = $this->buildSkill(skill: $skill);
        }

        return new Skills(
            skills: $skills,
        );
    }

    private function buildSkill(array $skill): Skill
    {
        $skillDetailedKeywords = [];
        foreach ($skill['DetailedKeywords'] as $skillDetailedKeyword) {
            $skillDetailedKeywords[] = $this->buildSkillDetailedKeyword(skillDetailedKeyword: $skillDetailedKeyword);
        }

        return new Skill(
            name: $skill['Name'],
            label: $skill['Label'],
            detailedKeywords: $skillDetailedKeywords,
        );
    }

    private function buildSkillDetailedKeyword(array $skillDetailedKeyword): DetailedKeyword
    {
        return new DetailedKeyword(
            keyword: $skillDetailedKeyword['Keyword'],
            level: $skillDetailedKeyword['Level'],
            experienceInYears: $skillDetailedKeyword['ExperienceInYears'],
        );
    }

    private function buildProjects(array $canonical): Projects
    {
        $projects = [];
        foreach ($canonical['Projects'] as $project) {
            $projects[] = $this->buildProject(project: $project);
        }

        return new Projects(
            projects: $projects,
        );
    }

    private function buildProject(array $project): Project
    {
        return new Project(
            name: $project['Name'],
            description: $project['Description'],
            entity: $project['Entity'],
            type: $project['Type'],
            startDate: $project['StartDate'],
            endDate: $project['EndDate'],
            highlights: $this->buildProjectHighlights(project: $project),
            keywords: $this->buildProjectKeywords(project: $project),
            roles: $this->buildProjectRoles(project: $project),
        );
    }

    /**
     * @return list<Highlight>
     */
    private function buildProjectHighlights(array $project): array
    {
        $highlights = [];
        foreach ($project['Highlights'] as $highlight) {
            $highlights[] = new Highlight(highlight: $highlight);
        }

        /* @psalm-var list<Highlight> $highlights */
        return $highlights;
    }

    /**
     * @return list<Keyword>
     */
    private function buildProjectKeywords(array $project): array
    {
        $keywords = [];
        foreach ($project['Keywords'] as $keyword) {
            $keywords[] = new Keyword(keyword: $keyword);
        }

        return $keywords;
    }

    /**
     * @return list<Role>
     */
    private function buildProjectRoles(array $project): array
    {
        $roles = [];
        foreach ($project['Roles'] as $role) {
            $roles[] = new Role(role: $role);
        }

        return $roles;
    }

    private function buildMeta(array $canonical): Meta
    {
        $content = $this->buildMetaContent(canonical: $canonical);

        return new Meta(
            canonical: $canonical['Meta']['Canonical'],
            version: $canonical['Meta']['Version'],
            lastModified: $canonical['Meta']['LastModified'],
            content: $content,
        );
    }

    private function buildMetaContent(array $canonical): Content
    {
        return new Content(
            labels: new Labels(
                skills: $canonical['Meta']['Content']['Labels']['Skills'],
                languages: $canonical['Meta']['Content']['Labels']['Languages'],
                language: $canonical['Meta']['Content']['Labels']['Language'],
                overview: $canonical['Meta']['Content']['Labels']['Overview'],
                projects: $canonical['Meta']['Content']['Labels']['Projects'],
                education: $canonical['Meta']['Content']['Labels']['Education'],
                competences: $canonical['Meta']['Content']['Labels']['Competences'],
                moreCompetences: $canonical['Meta']['Content']['Labels']['MoreCompetences'],
                experienceInYears: $canonical['Meta']['Content']['Labels']['ExperienceInYears'],
                experienceLevel: $canonical['Meta']['Content']['Labels']['ExperienceLevel'],
                page: $canonical['Meta']['Content']['Labels']['Page'],
                pageOf: $canonical['Meta']['Content']['Labels']['PageOf'],
                years: new Years(
                    singular: $canonical['Meta']['Content']['Labels']['Years']['Singular'],
                    plural: $canonical['Meta']['Content']['Labels']['Years']['Plural'],
                ),
            ),
        );
    }
}
