<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Factories\Resume;

use Smoothie\ResumeExporter\Domain\Resume\Basics\Basic;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Locations\Location;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews\Item;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews\Overview;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles\Profile;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles\Profiles;
use Smoothie\ResumeExporter\Domain\Resume\Educations\Education;
use Smoothie\ResumeExporter\Domain\Resume\Educations\Educations;
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
use Smoothie\ResumeExporter\Domain\Resume\ResumeId;
use Smoothie\ResumeExporter\Domain\Resume\Skills\DetailedKeyword;
use Smoothie\ResumeExporter\Domain\Resume\Skills\Skill;
use Smoothie\ResumeExporter\Domain\Resume\Skills\Skills;

class ResumeFactory
{
    private function __construct()
    {
    }

    public static function create(array $resume): Resume
    {
        $profiles = [];
        foreach ($resume['Basic']['Profiles'] as $profile) {
            $profiles[] = new Profile(
                network: $profile['Network'],
                url: $profile['Url'],
                username: $profile['Username'],
            );
        }

        $overviewItems = [];
        if (isset($resume['Basic']['Overview']['Items'])) {
            foreach ($resume['Basic']['Overview']['Items'] as $overviewItem) {
                $overviewItems[] = new Item(
                    label: $overviewItem['Label'],
                    value: $overviewItem['Value'],
                );
            }
        }

        $educations = [];
        foreach ($resume['Education'] as $education) {
            $educations[] = new Education(
                area: $education['Area'],
                endDate: $education['EndDate'],
                startDate: $education['StartDate'],
                studyType: $education['StudyType'],
            );
        }
        $languages = [];
        foreach ($resume['Languages'] as $language) {
            $languages[] = new Language(
                language: $language['Language'],
                fluency: $language['Fluency'],
            );
        }
        $skills = [];
        foreach ($resume['Skills'] as $skill) {
            $detailedKeywords = [];
            foreach ($skill['DetailedKeywords'] as $detailedKeyword) {
                $detailedKeywords[] = new DetailedKeyword(
                    keyword: $detailedKeyword['Keyword'],
                    level: $detailedKeyword['Level'],
                    experienceInYears: $detailedKeyword['ExperienceInYears'],
                );
            }

            $skills[] = new Skill(
                name: $skill['Name'],
                label: $skill['Label'],
                detailedKeywords: $detailedKeywords,
            );
        }

        $projects = [];
        foreach ($resume['Projects'] as $project) {
            $highlights = [];
            $keywords = [];
            $roles = [];
            foreach ($project['Highlights'] as $highlight) {
                $highlights[] = new Highlight(highlight: $highlight);
            }

            foreach ($project['Keywords'] as $keyword) {
                $keywords[] = new Keyword(keyword: $keyword);
            }

            foreach ($project['Roles'] as $role) {
                $roles[] = new Role(role: $role);
            }

            $projects[] = new Project(
                name: $project['Name'],
                description: $project['Description'],
                entity: $project['Entity'],
                type: $project['Type'],
                startDate: $project['StartDate'],
                endDate: $project['EndDate'],
                highlights: $highlights,
                keywords: $keywords,
                roles: $roles,
            );
        }

        return new Resume(
            id: new ResumeId($resume['Meta']['Internal']['ResumeId']),
            basic: new Basic(
                email: $resume['Basic']['Email'],
                label: $resume['Basic']['Label'],
                name: $resume['Basic']['Name'],
                phone: $resume['Basic']['Phone'],
                summary: $resume['Basic']['Summary'],
                url: $resume['Basic']['Url'],
                location: new Location(
                    address: $resume['Basic']['Location']['Address'],
                    city: $resume['Basic']['Location']['City'],
                    countryCode: $resume['Basic']['Location']['CountryCode'],
                    postalCode: $resume['Basic']['Location']['PostalCode'],
                ),
                profiles: new Profiles(profiles: $profiles),
                overview: new Overview(items: $overviewItems),
            ),
            educations: new Educations($educations),
            languages: new Languages($languages),
            skills: new Skills($skills),
            projects: new Projects($projects),
            meta: new Meta(
                canonical: $resume['Meta']['Canonical'],
                version: $resume['Meta']['Version'],
                lastModified: $resume['Meta']['LastModified'],
                content: new Content(
                    labels: new Labels(
                        skills: $resume['Meta']['Content']['Labels']['Skills'],
                        languages: $resume['Meta']['Content']['Labels']['Languages'],
                        language: $resume['Meta']['Content']['Labels']['Language'],
                        overview: $resume['Meta']['Content']['Labels']['Overview'],
                        projects: $resume['Meta']['Content']['Labels']['Projects'],
                        education: $resume['Meta']['Content']['Labels']['Education'],
                        competences: $resume['Meta']['Content']['Labels']['Competences'],
                        moreCompetences: $resume['Meta']['Content']['Labels']['MoreCompetences'],
                        experienceInYears: $resume['Meta']['Content']['Labels']['ExperienceInYears'],
                        experienceLevel: $resume['Meta']['Content']['Labels']['ExperienceLevel'],
                        page: $resume['Meta']['Content']['Labels']['Page'],
                        pageOf: $resume['Meta']['Content']['Labels']['PageOf'],
                        years: new Years(
                            singular: $resume['Meta']['Content']['Labels']['Years']['Singular'],
                            plural: $resume['Meta']['Content']['Labels']['Years']['Plural'],
                        ),
                    ),
                ),
            ),
        );
    }
}
