<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Resume;

use Smoothie\ResumeExporter\Domain\Resume\Basics\Basic;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Locations\Location;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews\Overview;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles\Profiles;
use Smoothie\ResumeExporter\Domain\Resume\Educations\Educations;
use Smoothie\ResumeExporter\Domain\Resume\Languages\Languages;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Content\Content;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Content\Labels;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Content\Years;
use Smoothie\ResumeExporter\Domain\Resume\Meta\Meta;
use Smoothie\ResumeExporter\Domain\Resume\Projects\Projects;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Domain\Resume\ResumeId;
use Smoothie\ResumeExporter\Domain\Resume\Skills\Skills;

class EmptyResume
{
    private function __construct()
    {
    }

    public static function create(): Resume
    {
        return new Resume(
            id: new ResumeId(''),
            basic: new Basic(
                email: '',
                label: '',
                name: '',
                phone: '',
                summary: '',
                url: '',
                location: new Location(
                    address: '',
                    city: '',
                    countryCode: '',
                    postalCode: '',
                ),
                profiles: new Profiles(profiles: []),
                overview: new Overview(items: []),
            ),
            educations: new Educations([]),
            languages: new Languages([]),
            skills: new Skills([]),
            projects: new Projects([]),
            meta: new Meta(
                canonical: '',
                version: '',
                lastModified: '',
                content: new Content(
                    labels: new Labels(
                        skills: '',
                        languages: '',
                        language: '',
                        overview: '',
                        projects: '',
                        education: '',
                        competences: '',
                        moreCompetences: '',
                        experienceInYears: '',
                        experienceLevel: '',
                        page: '',
                        pageOf: '',
                        years: new Years(singular: '', plural: ''),
                    ),
                ),
            ),
        );
    }
}
