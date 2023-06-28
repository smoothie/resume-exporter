<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Integration\Infrastructure\File;

use Psr\Log\NullLogger;
use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Resume\Exceptions\InvalidCanonicalReceivedException;
use Smoothie\ResumeExporter\Infrastructure\File\JsonResumeFactory;
use Smoothie\ResumeExporter\Infrastructure\File\JsonResumeRepository;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemRepository;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemsFactory;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessStrategy;
use Smoothie\Tests\ResumeExporter\BasicTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Validation;

/**
 * @group integration
 * @group integration-infrastructure
 * @group integration-infrastructure-file
 * @group integration-infrastructure-file-json-resume-repository
 */
class JsonResumeRepositoryTest extends BasicTestCase
{
    /**
     * @dataProvider provideFirstAndTranslateGoodPath
     */
    public function testFirstAndTranslate(array $assertions, array $expectations): void
    {
        $jsonResumeRepository = $this->buildJsonResumeRepository();

        $input = $this->buildInput(assertedInput: $assertions['input']);

        try {
            $resume = $jsonResumeRepository->firstAndTranslate(input: $input);
        } catch (\Throwable $exception) {
            throw $exception;
        }

        static::assertSame(expected: $expectations['resume'], actual: $resume->toArray());
    }

    /**
     * @dataProvider provideTranslateGoodPath
     */
    public function testTranslate(array $assertions, array $expectations): void
    {
        $jsonResumeRepository = $this->buildJsonResumeRepository();

        $input = $this->buildInput(assertedInput: $assertions['input']);

        $resume = $jsonResumeRepository->translateToCanonical(input: $input, canonicalData: $assertions['canonical']);

        static::assertSame(expected: $expectations['resume'], actual: $resume->toArray());
    }

    /**
     * @dataProvider provideTranslateNotSoGoodPath
     */
    public function testTranslateThrowsHard(array $assertions, array $expectations): void
    {
        $jsonResumeRepository = $this->buildJsonResumeRepository();

        $input = $this->buildInput(assertedInput: $assertions['input']);

        $this->expectException($expectations['exception']);
        $jsonResumeRepository->translateToCanonical(input: $input, canonicalData: $assertions['canonical']);
    }

    private function buildInput(array $assertedInput): Input
    {
        return new Input(
            inputId: $assertedInput['inputId'],
            inputSource: $assertedInput['inputSource'],
            mapSource: $assertedInput['mapSource'],
            input: $assertedInput['input'],
            map: $assertedInput['map'],
        );
    }

    private function buildJsonResumeRepository(): JsonResumeRepository
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $mappingStrategy = new PropertyAccessStrategy(
            propertyAccessor: $propertyAccessor,
            logger: new NullLogger(),
            propertyAccessMapItemsFactory: new PropertyAccessMapItemsFactory(),
            mapItemRepository: new PropertyAccessMapItemRepository(propertyAccessor: $propertyAccessor),
        );

        $validator = Validation::createValidatorBuilder()->getValidator();
        $resumeFactory = new JsonResumeFactory(validator: $validator);

        return new JsonResumeRepository(
            mappingStrategy: $mappingStrategy,
            resumeFactory: $resumeFactory,
            propertyAccessor: $propertyAccessor,
        );
    }

    private function provideFirstAndTranslateGoodPath(): array
    {
        return [
            'simple' => [
                'assertions' => [
                    'input' => [
                        'inputId' => 'some-unique-internal-identifier',
                        'inputSource' => 'unused',
                        'mapSource' => 'unused',
                        'input' => json_decode(
                            json: '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                            associative: true,
                        ),
                        'map' => [
                            '[basics][email]' => '[Basic][Email]',
                            '[basics][label]' => '[Basic][Label]',
                            '[basics][name]' => '[Basic][Name]',
                            '[basics][phone]' => '[Basic][Phone]',
                            '[basics][summary]' => '[Basic][Summary]',
                            '[basics][url]' => '[Basic][Url]',
                            '[basics][location][address]' => '[Basic][Location][Address]',
                            '[basics][location][city]' => '[Basic][Location][City]',
                            '[basics][location][countryCode]' => '[Basic][Location][CountryCode]',
                            '[basics][location][postalCode]' => '[Basic][Location][PostalCode]',
                            '[basics][profiles][*][network]' => '[Basic][Profiles][*][Network]',
                            '[basics][profiles][*][url]' => '[Basic][Profiles][*][Url]',
                            '[basics][profiles][*][username]' => '[Basic][Profiles][*][Username]',
                            '[basics][_overview][items][*][label]' => '[Basic][Overview][Items][*][Label]',
                            '[basics][_overview][items][*][value]' => '[Basic][Overview][Items][*][Value]',
                            '[education][*][area]' => '[Education][*][Area]',
                            '[education][*][endDate]' => '[Education][*][EndDate]',
                            '[education][*][startDate]' => '[Education][*][StartDate]',
                            '[education][*][studyType]' => '[Education][*][StudyType]',
                            '[languages][*][language]' => '[Languages][*][Language]',
                            '[languages][*][fluency]' => '[Languages][*][Fluency]',
                            '[projects][*][name]' => '[Projects][*][Name]',
                            '[projects][*][description]' => '[Projects][*][Description]',
                            '[projects][*][entity]' => '[Projects][*][Entity]',
                            '[projects][*][type]' => '[Projects][*][Type]',
                            '[projects][*][startDate]' => '[Projects][*][StartDate]',
                            '[projects][*][endDate]' => '[Projects][*][EndDate]',
                            '[projects][*][highlights][*]' => '[Projects][*][Highlights][*]',
                            '[projects][*][keywords][*]' => '[Projects][*][Keywords][*]',
                            '[projects][*][roles][*]' => '[Projects][*][Roles][*]',
                            '[skills][*][name]' => '[Skills][*][Name]',
                            '[skills][*][_label]' => '[Skills][*][Label]',
                            '[skills][*][_detailedKeywords][*][keyword]' => '[Skills][*][DetailedKeywords][*][Keyword]',
                            '[skills][*][_detailedKeywords][*][level]' => '[Skills][*][DetailedKeywords][*][Level]',
                            '[skills][*][_detailedKeywords][*][experienceInYears]' => '[Skills][*][DetailedKeywords][*][ExperienceInYears]',
                            '[meta][canonical]' => '[Meta][Canonical]',
                            '[meta][version]' => '[Meta][Version]',
                            '[meta][lastModified]' => '[Meta][LastModified]',
                            '[meta][_content][labels][skills]' => '[Meta][Content][Labels][Skills]',
                            '[meta][_content][labels][languages]' => '[Meta][Content][Labels][Languages]',
                            '[meta][_content][labels][language]' => '[Meta][Content][Labels][Language]',
                            '[meta][_content][labels][overview]' => '[Meta][Content][Labels][Overview]',
                            '[meta][_content][labels][projects]' => '[Meta][Content][Labels][Projects]',
                            '[meta][_content][labels][education]' => '[Meta][Content][Labels][Education]',
                            '[meta][_content][labels][competences]' => '[Meta][Content][Labels][Competences]',
                            '[meta][_content][labels][moreCompetences]' => '[Meta][Content][Labels][MoreCompetences]',
                            '[meta][_content][labels][experienceInYears]' => '[Meta][Content][Labels][ExperienceInYears]',
                            '[meta][_content][labels][experienceLevel]' => '[Meta][Content][Labels][ExperienceLevel]',
                            '[meta][_content][labels][page]' => '[Meta][Content][Labels][Page]',
                            '[meta][_content][labels][pageOf]' => '[Meta][Content][Labels][PageOf]',
                            '[meta][_content][labels][years][singular]' => '[Meta][Content][Labels][Years][Singular]',
                            '[meta][_content][labels][years][plural]' => '[Meta][Content][Labels][Years][Plural]',
                        ],
                    ],
                ],
                'expectations' => [
                    'resume' => [
                        'Basic' => [
                            'Email' => 'basic.email',
                            'Label' => 'basic.label',
                            'Name' => 'basic.name',
                            'Phone' => 'basic.phone',
                            'Summary' => 'basic.summary',
                            'Url' => 'basic.url',
                            'Location' => [
                                'Address' => 'basic.location.address',
                                'City' => 'basic.location.city',
                                'CountryCode' => 'basic.location.countryCode',
                                'PostalCode' => 'basic.location.postalCode',
                            ],
                            'Profiles' => [
                                0 => [
                                    'Network' => 'basic.profiles.0.network',
                                    'Url' => 'basic.profiles.0.url',
                                    'Username' => 'basic.profiles.0.username',
                                ],
                                1 => [
                                    'Network' => 'basic.profiles.*.network',
                                    'Url' => 'basic.profiles.*.url',
                                    'Username' => 'basic.profiles.*.username',
                                ],
                            ],
                            'Overview' => [
                                'Items' => [
                                    0 => [
                                        'Label' => 'basic.overview.items.0.label',
                                        'Value' => 'basic.overview.items.0.value',
                                    ],
                                    1 => [
                                        'Label' => 'basic.overview.items.1.label',
                                        'Value' => 'basic.overview.items.1.value',
                                    ],
                                    2 => [
                                        'Label' => 'basic.overview.items.*.label',
                                        'Value' => 'basic.overview.items.*.value',
                                    ],
                                ],
                            ],
                        ],
                        'Education' => [
                            0 => [
                                'Area' => 'education.0.area',
                                'EndDate' => 'education.0.endDate',
                                'StartDate' => 'education.0.startDate',
                                'StudyType' => 'education.0.studyType',
                            ],
                            1 => [
                                'Area' => 'education.1.area',
                                'EndDate' => 'education.1.endDate',
                                'StartDate' => 'education.1.startDate',
                                'StudyType' => 'education.1.studyType',
                            ],
                        ],
                        'Languages' => [
                            0 => [
                                'Language' => 'languages.*.language',
                                'Fluency' => 'languages.*.fluency',
                            ],
                        ],
                        'Projects' => [
                            0 => [
                                'Name' => 'projects.0.name',
                                'Description' => 'projects.0.description',
                                'Entity' => 'projects.0.entity',
                                'Type' => 'projects.0.type',
                                'StartDate' => 'projects.0.startDate',
                                'EndDate' => 'projects.0.endDate',
                                'Highlights' => [
                                    0 => 'projects.0.highlights.0',
                                    1 => 'projects.0.highlights.1',
                                    2 => 'projects.0.highlights.2',
                                    3 => 'projects.0.highlights.*',
                                ],
                                'Keywords' => [
                                    0 => 'projects.0.keywords.*',
                                ],
                                'Roles' => [
                                    0 => 'projects.0.roles.*',
                                ],
                            ],
                            1 => [
                                'Name' => 'projects.*.name',
                                'Description' => 'projects.*.description',
                                'Entity' => 'projects.*.entity',
                                'Type' => 'projects.*.type',
                                'StartDate' => 'projects.*.startDate',
                                'EndDate' => 'projects.*.endDate',
                                'Highlights' => [
                                    0 => 'projects.*.highlights.*',
                                ],
                                'Keywords' => [
                                    0 => 'projects.*.keywords.*',
                                ],
                                'Roles' => [
                                    0 => 'projects.*.roles.*',
                                ],
                            ],
                        ],
                        'Skills' => [
                            0 => [
                                'Name' => 'skills.0.name',
                                'Label' => 'skills.0.label',
                                'DetailedKeywords' => [
                                    0 => [
                                        'Keyword' => 'skills.0.detailedKeywords.0.keyword',
                                        'Level' => 'skills.0.detailedKeywords.0.level',
                                        'ExperienceInYears' => 'skills.0.detailedKeywords.0.experienceInYears',
                                    ],
                                    1 => [
                                        'Keyword' => 'skills.0.detailedKeywords.1.keyword',
                                        'Level' => 'skills.0.detailedKeywords.1.level',
                                        'ExperienceInYears' => 'skills.0.detailedKeywords.1.experienceInYears',
                                    ],
                                    2 => [
                                        'Keyword' => 'skills.0.detailedKeywords.*.keyword',
                                        'Level' => 'skills.0.detailedKeywords.*.level',
                                        'ExperienceInYears' => 'skills.0.detailedKeywords.*.experienceInYears',
                                    ],
                                ],
                            ],
                            1 => [
                                'Name' => 'skills.*.name',
                                'Label' => 'skills.*._label',
                                'DetailedKeywords' => [
                                    0 => [
                                        'Keyword' => 'skills.*.detailedKeywords.*.keyword',
                                        'Level' => 'skills.*.detailedKeywords.*.level',
                                        'ExperienceInYears' => 'skills.*.detailedKeywords.*.experienceInYears',
                                    ],
                                ],
                            ],
                        ],
                        'Meta' => [
                            'Canonical' => 'meta.canonical',
                            'Version' => 'meta.version',
                            'LastModified' => 'meta.lastModified',
                            'Internal' => [
                                'ResumeId' => 'some-unique-internal-identifier',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => 'meta.content.labels.skills',
                                    'Languages' => 'meta.content.labels.languages',
                                    'Language' => 'meta.content.labels.language',
                                    'Overview' => 'meta.content.labels.overview',
                                    'Projects' => 'meta.content.labels.projects',
                                    'Education' => 'meta.content.labels.education',
                                    'Competences' => 'meta.content.labels.competences',
                                    'MoreCompetences' => 'meta.content.labels.moreCompetences',
                                    'ExperienceInYears' => 'meta.content.labels.experienceInYears',
                                    'ExperienceLevel' => 'meta.content.labels.experienceLevel',
                                    'Page' => 'meta.content.labels.page',
                                    'PageOf' => 'meta.content.label.pageOfs',
                                    'Years' => [
                                        'Singular' => 'meta.content.labels.years.singular',
                                        'Plural' => 'meta.content.label.years.plurals',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // TODO            'when_everything_is_empty' => [],
        ];
    }

    private function provideTranslateGoodPath(): array
    {
        return [
            'simple_max_one_item' => [
                'assertions' => [
                    'input' => [
                        'inputId' => 'some-unique-internal-identifier',
                        'inputSource' => 'unused',
                        'mapSource' => 'unused',
                        'input' => [],
                        'map' => [],
                    ],
                    'canonical' => [
                        'Basic' => [
                            'Email' => 'basic.email',
                            'Label' => 'basic.label',
                            'Name' => 'basic.name',
                            'Phone' => 'basic.phone',
                            'Summary' => 'basic.summary',
                            'Url' => 'basic.url',
                            'Location' => [
                                'Address' => 'basic.location.address',
                                'City' => 'basic.location.city',
                                'CountryCode' => 'basic.location.countryCode',
                                'PostalCode' => 'basic.location.postalCode',
                            ],
                            'Profiles' => [
                                [
                                    'Network' => 'profiles.0.network',
                                    'Url' => 'profiles.0.url',
                                    'Username' => 'profiles.0.username',
                                ],
                            ],
                            'Overview' => [
                                'Items' => [
                                    [
                                        'Label' => 'overview.items.0.label',
                                        'Value' => 'overview.items.0.value',
                                    ],
                                ],
                            ],
                        ],
                        'Education' => [
                            [
                                'Area' => 'education.0.area',
                                'EndDate' => 'education.0.endDate',
                                'StartDate' => 'education.0.startDate',
                                'StudyType' => 'education.0.studyType',
                            ],
                        ],
                        'Languages' => [
                            [
                                'Language' => 'languages.0.language',
                                'Fluency' => 'languages.0.fluency',
                            ],
                        ],
                        'Projects' => [
                            [
                                'Name' => 'projects.0.name',
                                'Description' => 'projects.0.description',
                                'Entity' => 'projects.0.entity',
                                'Type' => 'projects.0.type',
                                'StartDate' => 'projects.0.startDate',
                                'EndDate' => 'projects.0.endDate',
                                'Highlights' => ['projects.0.highlights.0'],
                                'Keywords' => ['projects.0.keywords.0'],
                                'Roles' => ['projects.0.roles.0'],
                            ],
                        ],
                        'Skills' => [
                            [
                                'Name' => 'skills.0.name',
                                'Label' => 'skills.0.label',
                                'DetailedKeywords' => [
                                    [
                                        'Keyword' => 'skills.0.detailedKeywords.keyword',
                                        'Level' => 'skills.0.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.0.detailedKeywords.experienceInYears',
                                    ],
                                ],
                            ],
                        ],
                        'Meta' => [
                            'Canonical' => 'meta.canonical',
                            'Version' => 'meta.version',
                            'LastModified' => 'meta.lastModified',
                            'Internal' => [
                                'ResumeId' => 'meta.internal.resumeId',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => 'meta.labels.skills',
                                    'Languages' => 'meta.labels.languages',
                                    'Language' => 'meta.labels.language',
                                    'Overview' => 'meta.labels.overview',
                                    'Projects' => 'meta.labels.projects',
                                    'Education' => 'meta.labels.education',
                                    'Competences' => 'meta.labels.competences',
                                    'MoreCompetences' => 'meta.labels.moreCompetences',
                                    'ExperienceInYears' => 'meta.labels.experienceInYears',
                                    'ExperienceLevel' => 'meta.labels.experienceLevel',
                                    'Page' => 'meta.labels.page',
                                    'PageOf' => 'meta.labels.pageOf',
                                    'Years' => [
                                        'Singular' => 'meta.labels.singular',
                                        'Plural' => 'meta.labels.plural',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectations' => [
                    'resume' => [
                        'Basic' => [
                            'Email' => 'basic.email',
                            'Label' => 'basic.label',
                            'Name' => 'basic.name',
                            'Phone' => 'basic.phone',
                            'Summary' => 'basic.summary',
                            'Url' => 'basic.url',
                            'Location' => [
                                'Address' => 'basic.location.address',
                                'City' => 'basic.location.city',
                                'CountryCode' => 'basic.location.countryCode',
                                'PostalCode' => 'basic.location.postalCode',
                            ],
                            'Profiles' => [
                                [
                                    'Network' => 'profiles.0.network',
                                    'Url' => 'profiles.0.url',
                                    'Username' => 'profiles.0.username',
                                ],
                            ],
                            'Overview' => [
                                'Items' => [
                                    [
                                        'Label' => 'overview.items.0.label',
                                        'Value' => 'overview.items.0.value',
                                    ],
                                ],
                            ],
                        ],
                        'Education' => [
                            [
                                'Area' => 'education.0.area',
                                'EndDate' => 'education.0.endDate',
                                'StartDate' => 'education.0.startDate',
                                'StudyType' => 'education.0.studyType',
                            ],
                        ],
                        'Languages' => [
                            [
                                'Language' => 'languages.0.language',
                                'Fluency' => 'languages.0.fluency',
                            ],
                        ],
                        'Projects' => [
                            [
                                'Name' => 'projects.0.name',
                                'Description' => 'projects.0.description',
                                'Entity' => 'projects.0.entity',
                                'Type' => 'projects.0.type',
                                'StartDate' => 'projects.0.startDate',
                                'EndDate' => 'projects.0.endDate',
                                'Highlights' => ['projects.0.highlights.0'],
                                'Keywords' => ['projects.0.keywords.0'],
                                'Roles' => ['projects.0.roles.0'],
                            ],
                        ],
                        'Skills' => [
                            [
                                'Name' => 'skills.0.name',
                                'Label' => 'skills.0.label',
                                'DetailedKeywords' => [
                                    [
                                        'Keyword' => 'skills.0.detailedKeywords.keyword',
                                        'Level' => 'skills.0.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.0.detailedKeywords.experienceInYears',
                                    ],
                                ],
                            ],
                        ],
                        'Meta' => [
                            'Canonical' => 'meta.canonical',
                            'Version' => 'meta.version',
                            'LastModified' => 'meta.lastModified',
                            'Internal' => [
                                'ResumeId' => 'some-unique-internal-identifier',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => 'meta.labels.skills',
                                    'Languages' => 'meta.labels.languages',
                                    'Language' => 'meta.labels.language',
                                    'Overview' => 'meta.labels.overview',
                                    'Projects' => 'meta.labels.projects',
                                    'Education' => 'meta.labels.education',
                                    'Competences' => 'meta.labels.competences',
                                    'MoreCompetences' => 'meta.labels.moreCompetences',
                                    'ExperienceInYears' => 'meta.labels.experienceInYears',
                                    'ExperienceLevel' => 'meta.labels.experienceLevel',
                                    'Page' => 'meta.labels.page',
                                    'PageOf' => 'meta.labels.pageOf',
                                    'Years' => [
                                        'Singular' => 'meta.labels.singular',
                                        'Plural' => 'meta.labels.plural',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple_max_three_items' => [
                'assertions' => [
                    'input' => [
                        'inputId' => 'some-unique-internal-identifier',
                        'inputSource' => 'unused',
                        'mapSource' => 'unused',
                        'input' => [],
                        'map' => [],
                    ],
                    'canonical' => [
                        'Basic' => [
                            'Email' => 'basic.email',
                            'Label' => 'basic.label',
                            'Name' => 'basic.name',
                            'Phone' => 'basic.phone',
                            'Summary' => 'basic.summary',
                            'Url' => 'basic.url',
                            'Location' => [
                                'Address' => 'basic.location.address',
                                'City' => 'basic.location.city',
                                'CountryCode' => 'basic.location.countryCode',
                                'PostalCode' => 'basic.location.postalCode',
                            ],
                            'Profiles' => [
                                [
                                    'Network' => 'profiles.0.network',
                                    'Url' => 'profiles.0.url',
                                    'Username' => 'profiles.0.username',
                                ],
                                [
                                    'Network' => 'profiles.1.network',
                                    'Url' => 'profiles.1.url',
                                    'Username' => 'profiles.1.username',
                                ],
                                [
                                    'Network' => 'profiles.2.network',
                                    'Url' => 'profiles.2.url',
                                    'Username' => 'profiles.2.username',
                                ],
                            ],
                            'Overview' => [
                                'Items' => [
                                    [
                                        'Label' => 'overview.items.0.label',
                                        'Value' => 'overview.items.0.value',
                                    ],
                                    [
                                        'Label' => 'overview.items.1.label',
                                        'Value' => 'overview.items.1.value',
                                    ],
                                    [
                                        'Label' => 'overview.items.2.label',
                                        'Value' => 'overview.items.2.value',
                                    ],
                                ],
                            ],
                        ],
                        'Education' => [
                            [
                                'Area' => 'education.0.area',
                                'EndDate' => 'education.0.endDate',
                                'StartDate' => 'education.0.startDate',
                                'StudyType' => 'education.0.studyType',
                            ],
                            [
                                'Area' => 'education.1.area',
                                'EndDate' => 'education.1.endDate',
                                'StartDate' => 'education.1.startDate',
                                'StudyType' => 'education.1.studyType',
                            ],
                            [
                                'Area' => 'education.2.area',
                                'EndDate' => 'education.2.endDate',
                                'StartDate' => 'education.2.startDate',
                                'StudyType' => 'education.2.studyType',
                            ],
                        ],
                        'Languages' => [
                            [
                                'Language' => 'languages.0.language',
                                'Fluency' => 'languages.0.fluency',
                            ],
                            [
                                'Language' => 'languages.1.language',
                                'Fluency' => 'languages.1.fluency',
                            ],
                            [
                                'Language' => 'languages.2.language',
                                'Fluency' => 'languages.2.fluency',
                            ],
                        ],
                        'Projects' => [
                            [
                                'Name' => 'projects.0.name',
                                'Description' => 'projects.0.description',
                                'Entity' => 'projects.0.entity',
                                'Type' => 'projects.0.type',
                                'StartDate' => 'projects.0.startDate',
                                'EndDate' => 'projects.0.endDate',
                                'Highlights' => [
                                    'projects.0.highlights.0',
                                    'projects.0.highlights.1',
                                    'projects.0.highlights.2',
                                ],
                                'Keywords' => [
                                    'projects.1.keywords.0',
                                    'projects.1.keywords.1',
                                    'projects.1.keywords.2',
                                ],
                                'Roles' => [
                                    'projects.2.roles.0',
                                    'projects.2.roles.1',
                                    'projects.2.roles.2',
                                ],
                            ],
                        ],
                        'Skills' => [
                            [
                                'Name' => 'skills.0.name',
                                'Label' => 'skills.0.label',
                                'DetailedKeywords' => [
                                    [
                                        'Keyword' => 'skills.0.detailedKeywords.keyword',
                                        'Level' => 'skills.0.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.0.detailedKeywords.experienceInYears',
                                    ],
                                    [
                                        'Keyword' => 'skills.1.detailedKeywords.keyword',
                                        'Level' => 'skills.1.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.1.detailedKeywords.experienceInYears',
                                    ],
                                    [
                                        'Keyword' => 'skills.2.detailedKeywords.keyword',
                                        'Level' => 'skills.2.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.2.detailedKeywords.experienceInYears',
                                    ],
                                ],
                            ],
                        ],
                        'Meta' => [
                            'Canonical' => 'meta.canonical',
                            'Version' => 'meta.version',
                            'LastModified' => 'meta.lastModified',
                            'Internal' => [
                                'ResumeId' => 'meta.internal.resumeId',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => 'meta.labels.skills',
                                    'Languages' => 'meta.labels.languages',
                                    'Language' => 'meta.labels.language',
                                    'Overview' => 'meta.labels.overview',
                                    'Projects' => 'meta.labels.projects',
                                    'Education' => 'meta.labels.education',
                                    'Competences' => 'meta.labels.competences',
                                    'MoreCompetences' => 'meta.labels.moreCompetences',
                                    'ExperienceInYears' => 'meta.labels.experienceInYears',
                                    'ExperienceLevel' => 'meta.labels.experienceLevel',
                                    'Page' => 'meta.labels.page',
                                    'PageOf' => 'meta.labels.pageOf',
                                    'Years' => [
                                        'Singular' => 'meta.labels.singular',
                                        'Plural' => 'meta.labels.plural',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectations' => [
                    'resume' => [
                        'Basic' => [
                            'Email' => 'basic.email',
                            'Label' => 'basic.label',
                            'Name' => 'basic.name',
                            'Phone' => 'basic.phone',
                            'Summary' => 'basic.summary',
                            'Url' => 'basic.url',
                            'Location' => [
                                'Address' => 'basic.location.address',
                                'City' => 'basic.location.city',
                                'CountryCode' => 'basic.location.countryCode',
                                'PostalCode' => 'basic.location.postalCode',
                            ],
                            'Profiles' => [
                                [
                                    'Network' => 'profiles.0.network',
                                    'Url' => 'profiles.0.url',
                                    'Username' => 'profiles.0.username',
                                ],
                                [
                                    'Network' => 'profiles.1.network',
                                    'Url' => 'profiles.1.url',
                                    'Username' => 'profiles.1.username',
                                ],
                                [
                                    'Network' => 'profiles.2.network',
                                    'Url' => 'profiles.2.url',
                                    'Username' => 'profiles.2.username',
                                ],
                            ],
                            'Overview' => [
                                'Items' => [
                                    [
                                        'Label' => 'overview.items.0.label',
                                        'Value' => 'overview.items.0.value',
                                    ],
                                    [
                                        'Label' => 'overview.items.1.label',
                                        'Value' => 'overview.items.1.value',
                                    ],
                                    [
                                        'Label' => 'overview.items.2.label',
                                        'Value' => 'overview.items.2.value',
                                    ],
                                ],
                            ],
                        ],
                        'Education' => [
                            [
                                'Area' => 'education.0.area',
                                'EndDate' => 'education.0.endDate',
                                'StartDate' => 'education.0.startDate',
                                'StudyType' => 'education.0.studyType',
                            ],
                            [
                                'Area' => 'education.1.area',
                                'EndDate' => 'education.1.endDate',
                                'StartDate' => 'education.1.startDate',
                                'StudyType' => 'education.1.studyType',
                            ],
                            [
                                'Area' => 'education.2.area',
                                'EndDate' => 'education.2.endDate',
                                'StartDate' => 'education.2.startDate',
                                'StudyType' => 'education.2.studyType',
                            ],
                        ],
                        'Languages' => [
                            [
                                'Language' => 'languages.0.language',
                                'Fluency' => 'languages.0.fluency',
                            ],
                            [
                                'Language' => 'languages.1.language',
                                'Fluency' => 'languages.1.fluency',
                            ],
                            [
                                'Language' => 'languages.2.language',
                                'Fluency' => 'languages.2.fluency',
                            ],
                        ],
                        'Projects' => [
                            [
                                'Name' => 'projects.0.name',
                                'Description' => 'projects.0.description',
                                'Entity' => 'projects.0.entity',
                                'Type' => 'projects.0.type',
                                'StartDate' => 'projects.0.startDate',
                                'EndDate' => 'projects.0.endDate',
                                'Highlights' => [
                                    'projects.0.highlights.0',
                                    'projects.0.highlights.1',
                                    'projects.0.highlights.2',
                                ],
                                'Keywords' => [
                                    'projects.1.keywords.0',
                                    'projects.1.keywords.1',
                                    'projects.1.keywords.2',
                                ],
                                'Roles' => [
                                    'projects.2.roles.0',
                                    'projects.2.roles.1',
                                    'projects.2.roles.2',
                                ],
                            ],
                        ],
                        'Skills' => [
                            [
                                'Name' => 'skills.0.name',
                                'Label' => 'skills.0.label',
                                'DetailedKeywords' => [
                                    [
                                        'Keyword' => 'skills.0.detailedKeywords.keyword',
                                        'Level' => 'skills.0.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.0.detailedKeywords.experienceInYears',
                                    ],
                                    [
                                        'Keyword' => 'skills.1.detailedKeywords.keyword',
                                        'Level' => 'skills.1.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.1.detailedKeywords.experienceInYears',
                                    ],
                                    [
                                        'Keyword' => 'skills.2.detailedKeywords.keyword',
                                        'Level' => 'skills.2.detailedKeywords.level',
                                        'ExperienceInYears' => 'skills.2.detailedKeywords.experienceInYears',
                                    ],
                                ],
                            ],
                        ],
                        'Meta' => [
                            'Canonical' => 'meta.canonical',
                            'Version' => 'meta.version',
                            'LastModified' => 'meta.lastModified',
                            'Internal' => [
                                'ResumeId' => 'some-unique-internal-identifier',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => 'meta.labels.skills',
                                    'Languages' => 'meta.labels.languages',
                                    'Language' => 'meta.labels.language',
                                    'Overview' => 'meta.labels.overview',
                                    'Projects' => 'meta.labels.projects',
                                    'Education' => 'meta.labels.education',
                                    'Competences' => 'meta.labels.competences',
                                    'MoreCompetences' => 'meta.labels.moreCompetences',
                                    'ExperienceInYears' => 'meta.labels.experienceInYears',
                                    'ExperienceLevel' => 'meta.labels.experienceLevel',
                                    'Page' => 'meta.labels.page',
                                    'PageOf' => 'meta.labels.pageOf',
                                    'Years' => [
                                        'Singular' => 'meta.labels.singular',
                                        'Plural' => 'meta.labels.plural',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'empty_values' => [
                'assertions' => [
                    'input' => [
                        'inputId' => 'some-unique-internal-identifier',
                        'inputSource' => 'unused',
                        'mapSource' => 'unused',
                        'input' => [],
                        'map' => [],
                    ],
                    'canonical' => [
                        'Basic' => [
                            'Email' => '',
                            'Label' => '',
                            'Name' => '',
                            'Phone' => '',
                            'Summary' => '',
                            'Url' => '',
                            'Location' => [
                                'Address' => '',
                                'City' => '',
                                'CountryCode' => '',
                                'PostalCode' => '',
                            ],
                            'Profiles' => [
                                [
                                    'Network' => '',
                                    'Url' => '',
                                    'Username' => '',
                                ],
                            ],
                            'Overview' => [
                                'Items' => [
                                    [
                                        'Label' => '',
                                        'Value' => '',
                                    ],
                                ],
                            ],
                        ],
                        'Education' => [
                            [
                                'Area' => '',
                                'EndDate' => '',
                                'StartDate' => '',
                                'StudyType' => '',
                            ],
                        ],
                        'Languages' => [
                            [
                                'Language' => '',
                                'Fluency' => '',
                            ],
                        ],
                        'Projects' => [
                            [
                                'Name' => '',
                                'Description' => '',
                                'Entity' => '',
                                'Type' => '',
                                'StartDate' => '',
                                'EndDate' => '',
                                'Highlights' => [],
                                'Keywords' => [],
                                'Roles' => [],
                            ],
                        ],
                        'Skills' => [
                            [
                                'Name' => '',
                                'Label' => '',
                                'DetailedKeywords' => [
                                    [
                                        'Keyword' => '',
                                        'Level' => '',
                                        'ExperienceInYears' => '',
                                    ],
                                ],
                            ],
                        ],
                        'Meta' => [
                            'Canonical' => '',
                            'Version' => '',
                            'LastModified' => '',
                            'Internal' => [
                                'ResumeId' => 'meta.internal.resumeId',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => '',
                                    'Languages' => '',
                                    'Language' => '',
                                    'Overview' => '',
                                    'Projects' => '',
                                    'Education' => '',
                                    'Competences' => '',
                                    'MoreCompetences' => '',
                                    'ExperienceInYears' => '',
                                    'ExperienceLevel' => '',
                                    'Page' => '',
                                    'PageOf' => '',
                                    'Years' => [
                                        'Singular' => '',
                                        'Plural' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectations' => [
                    'resume' => [
                        'Basic' => [
                            'Email' => '',
                            'Label' => '',
                            'Name' => '',
                            'Phone' => '',
                            'Summary' => '',
                            'Url' => '',
                            'Location' => [
                                'Address' => '',
                                'City' => '',
                                'CountryCode' => '',
                                'PostalCode' => '',
                            ],
                            'Profiles' => [
                                [
                                    'Network' => '',
                                    'Url' => '',
                                    'Username' => '',
                                ],
                            ],
                            'Overview' => [
                                'Items' => [
                                    [
                                        'Label' => '',
                                        'Value' => '',
                                    ],
                                ],
                            ],
                        ],
                        'Education' => [
                            [
                                'Area' => '',
                                'EndDate' => '',
                                'StartDate' => '',
                                'StudyType' => '',
                            ],
                        ],
                        'Languages' => [
                            [
                                'Language' => '',
                                'Fluency' => '',
                            ],
                        ],
                        'Projects' => [
                            [
                                'Name' => '',
                                'Description' => '',
                                'Entity' => '',
                                'Type' => '',
                                'StartDate' => '',
                                'EndDate' => '',
                                'Highlights' => [],
                                'Keywords' => [],
                                'Roles' => [],
                            ],
                        ],
                        'Skills' => [
                            [
                                'Name' => '',
                                'Label' => '',
                                'DetailedKeywords' => [
                                    [
                                        'Keyword' => '',
                                        'Level' => '',
                                        'ExperienceInYears' => '',
                                    ],
                                ],
                            ],
                        ],
                        'Meta' => [
                            'Canonical' => '',
                            'Version' => '',
                            'LastModified' => '',
                            'Internal' => [
                                'ResumeId' => 'some-unique-internal-identifier',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => '',
                                    'Languages' => '',
                                    'Language' => '',
                                    'Overview' => '',
                                    'Projects' => '',
                                    'Education' => '',
                                    'Competences' => '',
                                    'MoreCompetences' => '',
                                    'ExperienceInYears' => '',
                                    'ExperienceLevel' => '',
                                    'Page' => '',
                                    'PageOf' => '',
                                    'Years' => [
                                        'Singular' => '',
                                        'Plural' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'no_array_values' => [
                'assertions' => [
                    'input' => [
                        'inputId' => 'some-unique-internal-identifier',
                        'inputSource' => 'unused',
                        'mapSource' => 'unused',
                        'input' => [],
                        'map' => [],
                    ],
                    'canonical' => [
                        'Basic' => [
                            'Email' => '',
                            'Label' => '',
                            'Name' => '',
                            'Phone' => '',
                            'Summary' => '',
                            'Url' => '',
                            'Location' => [
                                'Address' => '',
                                'City' => '',
                                'CountryCode' => '',
                                'PostalCode' => '',
                            ],
                            'Profiles' => [],
                            'Overview' => ['Items' => []],
                        ],
                        'Education' => [],
                        'Languages' => [],
                        'Projects' => [],
                        'Skills' => [],
                        'Meta' => [
                            'Canonical' => '',
                            'Version' => '',
                            'LastModified' => '',
                            'Internal' => [
                                'ResumeId' => 'some-unique-internal-identifier',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => '',
                                    'Languages' => '',
                                    'Language' => '',
                                    'Overview' => '',
                                    'Projects' => '',
                                    'Education' => '',
                                    'Competences' => '',
                                    'MoreCompetences' => '',
                                    'ExperienceInYears' => '',
                                    'ExperienceLevel' => '',
                                    'Page' => '',
                                    'PageOf' => '',
                                    'Years' => [
                                        'Singular' => '',
                                        'Plural' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectations' => [
                    'resume' => [
                        'Basic' => [
                            'Email' => '',
                            'Label' => '',
                            'Name' => '',
                            'Phone' => '',
                            'Summary' => '',
                            'Url' => '',
                            'Location' => [
                                'Address' => '',
                                'City' => '',
                                'CountryCode' => '',
                                'PostalCode' => '',
                            ],
                            'Profiles' => [],
                            'Overview' => ['Items' => []],
                        ],
                        'Education' => [],
                        'Languages' => [],
                        'Projects' => [],
                        'Skills' => [],
                        'Meta' => [
                            'Canonical' => '',
                            'Version' => '',
                            'LastModified' => '',
                            'Internal' => [
                                'ResumeId' => 'some-unique-internal-identifier',
                            ],
                            'Content' => [
                                'Labels' => [
                                    'Skills' => '',
                                    'Languages' => '',
                                    'Language' => '',
                                    'Overview' => '',
                                    'Projects' => '',
                                    'Education' => '',
                                    'Competences' => '',
                                    'MoreCompetences' => '',
                                    'ExperienceInYears' => '',
                                    'ExperienceLevel' => '',
                                    'Page' => '',
                                    'PageOf' => '',
                                    'Years' => [
                                        'Singular' => '',
                                        'Plural' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function provideTranslateNotSoGoodPath(): array
    {
        return [
            'all_missing' => [
                'assertions' => [
                    'input' => [
                        'inputId' => 'some-unique-internal-identifier',
                        'inputSource' => 'unused',
                        'mapSource' => 'unused',
                        'input' => [],
                        'map' => [],
                    ],
                    'canonical' => [],
                ],
                'expectations' => [
                    'exception' => InvalidCanonicalReceivedException::class,
                ],
            ],
        ];
    }
}
