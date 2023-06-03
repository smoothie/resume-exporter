<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Integration\Infrastructure\File;

use Psr\Log\NullLogger;
use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputFormat;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Infrastructure\File\DomPdfBuilder;
use Smoothie\ResumeExporter\Infrastructure\File\DomPdfRepository;
use Smoothie\ResumeExporter\Infrastructure\File\FilesystemRepository;
use Smoothie\ResumeExporter\Infrastructure\File\PdfResumeFactory;
use Smoothie\ResumeExporter\Infrastructure\File\PdfResumeRepository;
use Smoothie\ResumeExporter\Infrastructure\File\TwigFactory;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemRepository;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemsFactory;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessStrategy;
use Smoothie\Tests\ResumeExporter\BasicTestCase;
use Smoothie\Tests\ResumeExporter\Doubles\Factories\Resume\ResumeFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @group integration
 * @group integration-infrastructure
 * @group integration-infrastructure-file
 * @group integration-infrastructure-file-pdf-resume-repository
 */
class PdfResumeRepositoryTest extends BasicTestCase
{
    //    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();
        //        $this->root = vfsStream::setup('some');
    }

    /**
     * @group integration-infrastructure-file-pdf-resume-repository-persist
     *
     * @dataProvider providePersistGoodPath
     */
    public function testPersist(array $assertions, array $expectations): void
    {
        $filesystem = \Mockery::mock(FilesystemRepository::class);
        $filesystem->shouldReceive('save')->withArgs(
            function (string $outputPath, string $outputData) use ($expectations): bool {
                $this->assertSame(expected: $expectations['outputPath'], actual: $outputPath);
                // be aware we are not testing the PDF generation at all

                return true;
            },
        )->times(1);

        $jsonResumeRepository = $this->buildPdfResumeRepository($filesystem);

        $output = $this->buildOutput(assertedOutput: $assertions['output']);

        try {
            $jsonResumeRepository->persist(output: $output);
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * @dataProvider provideTranslateFromCanonicalGoodPath
     */
    public function testTranslateFromCanonical(array $assertions, array $expectations): void
    {
        $filesystem = \Mockery::mock(FilesystemRepository::class);
        $jsonResumeRepository = $this->buildPdfResumeRepository($filesystem);

        $output = $this->buildOutput(assertedOutput: $assertions['output']);

        try {
            $pdfData = $jsonResumeRepository->translateFromCanonical(output: $output);
        } catch (\Throwable $exception) {
            throw $exception;
        }

        static::assertSame(expected: $expectations['pdfData'], actual: $pdfData);
    }

    private function buildPdfResumeRepository(FilesystemRepository $filesystem,
    ): PdfResumeRepository {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $logger = new NullLogger();

        $propertyAccessMappingStrategy = new PropertyAccessStrategy(
            propertyAccessor: $propertyAccessor,
            logger: $logger,
            propertyAccessMapItemsFactory: new PropertyAccessMapItemsFactory(),
            mapItemRepository: new PropertyAccessMapItemRepository(propertyAccessor: $propertyAccessor),
        );

        $resumeFactory = new PdfResumeFactory();

        $twigFactory = new TwigFactory();

        $domPdfBuilder = new DomPdfBuilder();

        $domPdfRepository = new DomPdfRepository(
            filesystem: $filesystem,
            twigFactory: $twigFactory,
            domPdfBuilder: $domPdfBuilder,
        );

        return new PdfResumeRepository(
            mappingStrategy: $propertyAccessMappingStrategy,
            resumeFactory: $resumeFactory,
            domPdfRepository: $domPdfRepository,
        );
    }

    private function buildOutput(array $assertedOutput): Output
    {
        $canonical = $this->buildResume(resume: $assertedOutput['canonical']);

        return new Output(
            mapSource: $assertedOutput['mapSource'],
            outputPath: $assertedOutput['outputPath'],
            outputTemplatePath: $assertedOutput['templatePath'],
            outputFormat: $assertedOutput['outputFormat'],
            mapSettings: $assertedOutput['mapSettings'],
            map: $assertedOutput['map'],
            canonical: $canonical,
        );
    }

    public function buildResume(array $resume): Resume
    {
        return ResumeFactory::create($resume);
    }

    private function providePersistGoodPath(): array
    {
        $outputFormat = OutputFormat::PDF;

        return [
            'simple_without_settings' => [
                'assertions' => [
                    'output' => [
                        'mapSource' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/map.json',
                        'outputPath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/output.pdf',
                        'templatePath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/en-template.html.twig',
                        'outputFormat' => $outputFormat,
                        'mapSettings' => [],
                        'map' => [
                            '[Basic][Email]' => '[basicsEmail]',
                            '[Basic][Label]' => '[basicsLabel]',
                            '[Basic][Location][Address]' => '[basicsLocationAddress]',
                            '[Basic][Location][City]' => '[basicsLocationCity]',
                            '[Basic][Location][CountryCode]' => '[basicsLocationCountryCode]',
                            '[Basic][Location][PostalCode]' => '[basicsLocationPostalCode]',
                            '[Basic][Name]' => '[basicsName]',
                            '[Basic][Overview][Items][*][Label]' => '[basicsOverview][*][overviewItemLabel]',
                            '[Basic][Overview][Items][*][Value]' => '[basicsOverview][*][overviewItemValue]',
                            '[Basic][Phone]' => '[basicsPhone]',
                            '[Basic][Profiles][*][Network]' => '[basicsProfiles][*][basicProfilesNetwork]',
                            '[Basic][Profiles][*][Url]' => '[basicsProfiles][*][basicProfilesUrl]',
                            '[Basic][Profiles][*][Username]' => '[basicsProfiles][*][basicProfilesUsername]',
                            '[Basic][Summary]' => '[basicsSummary]',
                            '[Basic][Url]' => '[basicsUrl]',
                            '[Education][*][Area]' => '[educations][*][educationArea]',
                            '[Education][*][EndDate]' => '[educations][*][educationEndDate]',
                            '[Education][*][StartDate]' => '[educations][*][educationStartDate]',
                            '[Education][*][StudyType]' => '[educations][*][educationStudyType]',
                            '[Languages][*][Fluency]' => '[languages][*][languageFluency]',
                            '[Languages][*][Language]' => '[languages][*][languageName]',
                            '[Meta][Canonical]' => '[metaCanonical]',
                            '[Meta][Content][Labels][Competences]' => '[labelCompetences]',
                            '[Meta][Content][Labels][Education]' => '[labelEducation]',
                            '[Meta][Content][Labels][ExperienceInYears]' => '[labelExperienceInYears]',
                            '[Meta][Content][Labels][Language]' => '[labelLanguage]',
                            '[Meta][Content][Labels][Languages]' => '[labelLanguages]',
                            '[Meta][Content][Labels][MoreCompetences]' => '[labelMoreCompetences]',
                            '[Meta][Content][Labels][Overview]' => '[labelOverview]',
                            '[Meta][Content][Labels][PageOf]' => '[labelPageOf]',
                            '[Meta][Content][Labels][Page]' => '[labelPage]',
                            '[Meta][Content][Labels][Projects]' => '[labelProjects]',
                            '[Meta][Content][Labels][Skills]' => '[labelSkills]',
                            '[Meta][Content][Labels][Years][Plural]' => '[labelYearsPlural]',
                            '[Meta][Content][Labels][Years][Singular]' => '[labelYearsSingular]',
                            '[Meta][LastModified]' => '[metaLastModified]',
                            '[Meta][Version]' => '[metaVersion]',
                            '[Projects][*][Description]' => '[projects][*][projectDescription]',
                            '[Projects][*][EndDate]' => '[projects][*][projectEndDate]',
                            '[Projects][*][Entity]' => '[projects][*][projectName]',
                            '[Projects][*][Highlights][*]' => '[projects][*][projectHighlights][*]',
                            '[Projects][*][Keywords][*]' => '[projects][*][projectKeywords][*]',
                            '[Projects][*][Name]' => '[projects][*][projectName]',
                            '[Projects][*][Roles][*]' => '[projects][*][projectRoles][*]',
                            '[Projects][*][StartDate]' => '[projects][*][projectStartDate]',
                            '[Skills][*][DetailedKeywords][*][ExperienceInYears]' => '[skills][*][keywords][*][skillKeywordExperienceInYears]',
                            '[Skills][*][DetailedKeywords][*][Keyword]' => '[skills][*][keywords][*][skillKeywordLabel]',
                            '[Skills][*][DetailedKeywords][*][Level]' => '[skills][*][keywords][*][skillKeywordLevel]',
                            '[Skills][*][Label]' => '[skills][*][label]',
                            '[Skills][*][Name]' => '[skills][*][name]',
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
                                    'Label' => 'skills.*.label',
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
                'expectations' => [
                    'templatePath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/en-template.html.twig',
                    'outputPath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/output.pdf',
                ],
            ],
            'with_two_valid_fonts' => [
                'assertions' => [
                    'output' => [
                        'mapSource' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/map.json',
                        'outputPath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/output.pdf',
                        'templatePath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/en-template.html.twig',
                        'outputFormat' => $outputFormat,
                        'mapSettings' => [
                            'fonts' => [
                                0 => [
                                    'family' => 'Poppins',
                                    'style' => 'normal',
                                    'weight' => 'normal',
                                    'fontFile' => '/home/vagrant/spielwiese/resume-exporter/resources/fonts/Poppins/Poppins-Regular.ttf',
                                ],
                                1 => [
                                    'family' => 'Poppins',
                                    'style' => 'italic',
                                    'weight' => 'normal',
                                    'fontFile' => '/home/vagrant/spielwiese/resume-exporter/resources/fonts/Poppins/Poppins-Italic.ttf',
                                ],
                            ],
                        ],
                        'map' => [
                            '[Basic][Email]' => '[basicsEmail]',
                            '[Basic][Label]' => '[basicsLabel]',
                            '[Basic][Location][Address]' => '[basicsLocationAddress]',
                            '[Basic][Location][City]' => '[basicsLocationCity]',
                            '[Basic][Location][CountryCode]' => '[basicsLocationCountryCode]',
                            '[Basic][Location][PostalCode]' => '[basicsLocationPostalCode]',
                            '[Basic][Name]' => '[basicsName]',
                            '[Basic][Overview][Items][*][Label]' => '[basicsOverview][*][overviewItemLabel]',
                            '[Basic][Overview][Items][*][Value]' => '[basicsOverview][*][overviewItemValue]',
                            '[Basic][Phone]' => '[basicsPhone]',
                            '[Basic][Profiles][*][Network]' => '[basicsProfiles][*][basicProfilesNetwork]',
                            '[Basic][Profiles][*][Url]' => '[basicsProfiles][*][basicProfilesUrl]',
                            '[Basic][Profiles][*][Username]' => '[basicsProfiles][*][basicProfilesUsername]',
                            '[Basic][Summary]' => '[basicsSummary]',
                            '[Basic][Url]' => '[basicsUrl]',
                            '[Education][*][Area]' => '[educations][*][educationArea]',
                            '[Education][*][EndDate]' => '[educations][*][educationEndDate]',
                            '[Education][*][StartDate]' => '[educations][*][educationStartDate]',
                            '[Education][*][StudyType]' => '[educations][*][educationStudyType]',
                            '[Languages][*][Fluency]' => '[languages][*][languageFluency]',
                            '[Languages][*][Language]' => '[languages][*][languageName]',
                            '[Meta][Canonical]' => '[metaCanonical]',
                            '[Meta][Content][Labels][Competences]' => '[labelCompetences]',
                            '[Meta][Content][Labels][Education]' => '[labelEducation]',
                            '[Meta][Content][Labels][ExperienceInYears]' => '[labelExperienceInYears]',
                            '[Meta][Content][Labels][Language]' => '[labelLanguage]',
                            '[Meta][Content][Labels][Languages]' => '[labelLanguages]',
                            '[Meta][Content][Labels][MoreCompetences]' => '[labelMoreCompetences]',
                            '[Meta][Content][Labels][Overview]' => '[labelOverview]',
                            '[Meta][Content][Labels][PageOf]' => '[labelPageOf]',
                            '[Meta][Content][Labels][Page]' => '[labelPage]',
                            '[Meta][Content][Labels][Projects]' => '[labelProjects]',
                            '[Meta][Content][Labels][Skills]' => '[labelSkills]',
                            '[Meta][Content][Labels][Years][Plural]' => '[labelYearsPlural]',
                            '[Meta][Content][Labels][Years][Singular]' => '[labelYearsSingular]',
                            '[Meta][LastModified]' => '[metaLastModified]',
                            '[Meta][Version]' => '[metaVersion]',
                            '[Projects][*][Description]' => '[projects][*][projectDescription]',
                            '[Projects][*][EndDate]' => '[projects][*][projectEndDate]',
                            '[Projects][*][Entity]' => '[projects][*][projectName]',
                            '[Projects][*][Highlights][*]' => '[projects][*][projectHighlights][*]',
                            '[Projects][*][Keywords][*]' => '[projects][*][projectKeywords][*]',
                            '[Projects][*][Name]' => '[projects][*][projectName]',
                            '[Projects][*][Roles][*]' => '[projects][*][projectRoles][*]',
                            '[Projects][*][StartDate]' => '[projects][*][projectStartDate]',
                            '[Skills][*][DetailedKeywords][*][ExperienceInYears]' => '[skills][*][keywords][*][skillKeywordExperienceInYears]',
                            '[Skills][*][DetailedKeywords][*][Keyword]' => '[skills][*][keywords][*][skillKeywordLabel]',
                            '[Skills][*][DetailedKeywords][*][Level]' => '[skills][*][keywords][*][skillKeywordLevel]',
                            '[Skills][*][Label]' => '[skills][*][label]',
                            '[Skills][*][Name]' => '[skills][*][name]',
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
                                    'Label' => 'skills.*.label',
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
                'expectations' => [
                    'templatePath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/en-template.html.twig',
                    'outputPath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/output.pdf',
                ],
            ],
            'draft_with_page_numbers' => [
                'assertions' => [
                    'output' => [
                        'mapSource' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/map.json',
                        'outputPath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/output.pdf',
                        'templatePath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/en-template.html.twig',
                        'outputFormat' => $outputFormat,
                        'mapSettings' => [
                            'pageNumbers' => [
                                'text' => 'Page {PAGE_NUM} of {PAGE_COUNT}',
                                'font' => 'Poppins',
                                'x' => 490,
                                'y' => 780,
                                'color' => [0.033, 0.033, 0.033],
                                'size' => 9,
                            ],
                        ],
                        'map' => [
                            '[Basic][Email]' => '[basicsEmail]',
                            '[Basic][Label]' => '[basicsLabel]',
                            '[Basic][Location][Address]' => '[basicsLocationAddress]',
                            '[Basic][Location][City]' => '[basicsLocationCity]',
                            '[Basic][Location][CountryCode]' => '[basicsLocationCountryCode]',
                            '[Basic][Location][PostalCode]' => '[basicsLocationPostalCode]',
                            '[Basic][Name]' => '[basicsName]',
                            '[Basic][Overview][Items][*][Label]' => '[basicsOverview][*][overviewItemLabel]',
                            '[Basic][Overview][Items][*][Value]' => '[basicsOverview][*][overviewItemValue]',
                            '[Basic][Phone]' => '[basicsPhone]',
                            '[Basic][Profiles][*][Network]' => '[basicsProfiles][*][basicProfilesNetwork]',
                            '[Basic][Profiles][*][Url]' => '[basicsProfiles][*][basicProfilesUrl]',
                            '[Basic][Profiles][*][Username]' => '[basicsProfiles][*][basicProfilesUsername]',
                            '[Basic][Summary]' => '[basicsSummary]',
                            '[Basic][Url]' => '[basicsUrl]',
                            '[Education][*][Area]' => '[educations][*][educationArea]',
                            '[Education][*][EndDate]' => '[educations][*][educationEndDate]',
                            '[Education][*][StartDate]' => '[educations][*][educationStartDate]',
                            '[Education][*][StudyType]' => '[educations][*][educationStudyType]',
                            '[Languages][*][Fluency]' => '[languages][*][languageFluency]',
                            '[Languages][*][Language]' => '[languages][*][languageName]',
                            '[Meta][Canonical]' => '[metaCanonical]',
                            '[Meta][Content][Labels][Competences]' => '[labelCompetences]',
                            '[Meta][Content][Labels][Education]' => '[labelEducation]',
                            '[Meta][Content][Labels][ExperienceInYears]' => '[labelExperienceInYears]',
                            '[Meta][Content][Labels][Language]' => '[labelLanguage]',
                            '[Meta][Content][Labels][Languages]' => '[labelLanguages]',
                            '[Meta][Content][Labels][MoreCompetences]' => '[labelMoreCompetences]',
                            '[Meta][Content][Labels][Overview]' => '[labelOverview]',
                            '[Meta][Content][Labels][PageOf]' => '[labelPageOf]',
                            '[Meta][Content][Labels][Page]' => '[labelPage]',
                            '[Meta][Content][Labels][Projects]' => '[labelProjects]',
                            '[Meta][Content][Labels][Skills]' => '[labelSkills]',
                            '[Meta][Content][Labels][Years][Plural]' => '[labelYearsPlural]',
                            '[Meta][Content][Labels][Years][Singular]' => '[labelYearsSingular]',
                            '[Meta][LastModified]' => '[metaLastModified]',
                            '[Meta][Version]' => '[metaVersion]',
                            '[Projects][*][Description]' => '[projects][*][projectDescription]',
                            '[Projects][*][EndDate]' => '[projects][*][projectEndDate]',
                            '[Projects][*][Entity]' => '[projects][*][projectName]',
                            '[Projects][*][Highlights][*]' => '[projects][*][projectHighlights][*]',
                            '[Projects][*][Keywords][*]' => '[projects][*][projectKeywords][*]',
                            '[Projects][*][Name]' => '[projects][*][projectName]',
                            '[Projects][*][Roles][*]' => '[projects][*][projectRoles][*]',
                            '[Projects][*][StartDate]' => '[projects][*][projectStartDate]',
                            '[Skills][*][DetailedKeywords][*][ExperienceInYears]' => '[skills][*][keywords][*][skillKeywordExperienceInYears]',
                            '[Skills][*][DetailedKeywords][*][Keyword]' => '[skills][*][keywords][*][skillKeywordLabel]',
                            '[Skills][*][DetailedKeywords][*][Level]' => '[skills][*][keywords][*][skillKeywordLevel]',
                            '[Skills][*][Label]' => '[skills][*][label]',
                            '[Skills][*][Name]' => '[skills][*][name]',
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
                                    'Label' => 'skills.*.label',
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
                'expectations' => [
                    'templatePath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/en-template.html.twig',
                    'outputPath' => '/home/vagrant/spielwiese/resume-exporter/resources/stale/some/output.pdf',
                ],
            ],
        ];
    }

    private function provideTranslateFromCanonicalGoodPath(): array
    {
        $outputFormat = OutputFormat::PDF;

        return [
            'all' => [
                'assertions' => [
                    'output' => [
                        'mapSource' => 'unused',
                        'outputPath' => 'unused',
                        'templatePath' => 'unused',
                        'outputFormat' => $outputFormat,
                        'mapSettings' => [
                            'types' => [
                                'default' => 'VALUE',
                                '[basicsOverview][*]' => 'TABLE',
                                '[basicsOverview][*][overviewItemLabel]' => 'TABLE_ROW',
                                '[basicsOverview][*][overviewItemValue]' => 'TABLE_ROW',
                                '[basicsProfiles][*]' => 'BLOCK',
                                '[basicsProfiles][*][basicProfilesNetwork]' => 'BLOCK_ITEM',
                                '[basicsProfiles][*][basicProfilesUrl]' => 'BLOCK_ITEM',
                                '[basicsProfiles][*][basicProfilesUsername]' => 'BLOCK_ITEM',
                                '[educations][*]' => 'TABLE',
                                '[educations][*][educationArea]' => 'TABLE_ROW',
                                '[educations][*][educationEndDate]' => 'TABLE_ROW',
                                '[educations][*][educationStartDate]' => 'TABLE_ROW',
                                '[educations][*][educationStudyType]' => 'TABLE_ROW',
                                '[languages][*]' => 'TABLE',
                                '[languages][*][languageFluency]' => 'TABLE_ROW',
                                '[languages][*][languageName]' => 'TABLE_ROW',
                                '[projects][*]' => 'BLOCK',
                                '[projects][*][projectDescription]' => 'BLOCK_ITEM',
                                '[projects][*][projectEndDate]' => 'BLOCK_ITEM',
                                '[projects][*][projectName]' => 'BLOCK_ITEM',
                                '[projects][*][projectHighlights][*]' => 'BLOCK',
                                '[projects][*][projectKeywords][*]' => 'BLOCK',
                                '[projects][*][projectRoles][*]' => 'BLOCK',
                                '[projects][*][projectStartDate]' => 'BLOCK_ITEM',
                                '[skills][*]' => 'BLOCK',
                                '[skills][*][label]' => 'BLOCK_ITEM',
                                '[skills][*][name]' => 'BLOCK_ITEM',
                                '[skills][*][keywords][*]' => 'TABLE',
                                '[skills][*][keywords][*][skillKeywordExperienceInYears]' => 'TABLE_ROW',
                                '[skills][*][keywords][*][skillKeywordLabel]' => 'TABLE_ROW',
                                '[skills][*][keywords][*][skillKeywordLevel]' => 'TABLE_ROW',
                            ],
                        ],
                        'map' => [
                            '[Basic][Email]' => '[basicsEmail]',
                            '[Basic][Label]' => '[basicsLabel]',
                            '[Basic][Location][Address]' => '[basicsLocationAddress]',
                            '[Basic][Location][City]' => '[basicsLocationCity]',
                            '[Basic][Location][CountryCode]' => '[basicsLocationCountryCode]',
                            '[Basic][Location][PostalCode]' => '[basicsLocationPostalCode]',
                            '[Basic][Name]' => '[basicsName]',
                            '[Basic][Overview][Items][*][Label]' => '[basicsOverview][*][overviewItemLabel]',
                            '[Basic][Overview][Items][*][Value]' => '[basicsOverview][*][overviewItemValue]',
                            '[Basic][Phone]' => '[basicsPhone]',
                            '[Basic][Profiles][*][Network]' => '[basicsProfiles][*][basicProfilesNetwork]',
                            '[Basic][Profiles][*][Url]' => '[basicsProfiles][*][basicProfilesUrl]',
                            '[Basic][Profiles][*][Username]' => '[basicsProfiles][*][basicProfilesUsername]',
                            '[Basic][Summary]' => '[basicsSummary]',
                            '[Basic][Url]' => '[basicsUrl]',
                            '[Education][*][Area]' => '[educations][*][educationArea]',
                            '[Education][*][EndDate]' => '[educations][*][educationEndDate]',
                            '[Education][*][StartDate]' => '[educations][*][educationStartDate]',
                            '[Education][*][StudyType]' => '[educations][*][educationStudyType]',
                            '[Languages][*][Fluency]' => '[languages][*][languageFluency]',
                            '[Languages][*][Language]' => '[languages][*][languageName]',
                            '[Meta][Canonical]' => '[metaCanonical]',
                            '[Meta][Content][Labels][Competences]' => '[labelCompetences]',
                            '[Meta][Content][Labels][Education]' => '[labelEducation]',
                            '[Meta][Content][Labels][ExperienceInYears]' => '[labelExperienceInYears]',
                            '[Meta][Content][Labels][Language]' => '[labelLanguage]',
                            '[Meta][Content][Labels][Languages]' => '[labelLanguages]',
                            '[Meta][Content][Labels][MoreCompetences]' => '[labelMoreCompetences]',
                            '[Meta][Content][Labels][Overview]' => '[labelOverview]',
                            '[Meta][Content][Labels][PageOf]' => '[labelPageOf]',
                            '[Meta][Content][Labels][Page]' => '[labelPage]',
                            '[Meta][Content][Labels][Projects]' => '[labelProjects]',
                            '[Meta][Content][Labels][Skills]' => '[labelSkills]',
                            '[Meta][Content][Labels][Years][Plural]' => '[labelYearsPlural]',
                            '[Meta][Content][Labels][Years][Singular]' => '[labelYearsSingular]',
                            '[Meta][LastModified]' => '[metaLastModified]',
                            '[Meta][Version]' => '[metaVersion]',
                            '[Projects][*][Description]' => '[projects][*][projectDescription]',
                            '[Projects][*][EndDate]' => '[projects][*][projectEndDate]',
                            '[Projects][*][Entity]' => '[projects][*][projectName]',
                            '[Projects][*][Highlights][*]' => '[projects][*][projectHighlights][*]',
                            '[Projects][*][Keywords][*]' => '[projects][*][projectKeywords][*]',
                            '[Projects][*][Name]' => '[projects][*][projectName]',
                            '[Projects][*][Roles][*]' => '[projects][*][projectRoles][*]',
                            '[Projects][*][StartDate]' => '[projects][*][projectStartDate]',
                            '[Skills][*][DetailedKeywords][*][ExperienceInYears]' => '[skills][*][keywords][*][skillKeywordExperienceInYears]',
                            '[Skills][*][DetailedKeywords][*][Keyword]' => '[skills][*][keywords][*][skillKeywordLabel]',
                            '[Skills][*][DetailedKeywords][*][Level]' => '[skills][*][keywords][*][skillKeywordLevel]',
                            '[Skills][*][Label]' => '[skills][*][label]',
                            '[Skills][*][Name]' => '[skills][*][name]',
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
                                    'Label' => 'skills.*.label',
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
                'expectations' => [
                    'outputPath' => '',
                    'pdfData' => [
                        'basicsEmail' => 'basic.email',
                        'basicsLabel' => 'basic.label',
                        'basicsLocationAddress' => 'basic.location.address',
                        'basicsLocationCity' => 'basic.location.city',
                        'basicsLocationCountryCode' => 'basic.location.countryCode',
                        'basicsLocationPostalCode' => 'basic.location.postalCode',
                        'basicsName' => 'basic.name',
                        'basicsPhone' => 'basic.phone',
                        'basicsSummary' => 'basic.summary',
                        'basicsUrl' => 'basic.url',
                        'metaCanonical' => 'meta.canonical',
                        'labelCompetences' => 'meta.content.labels.competences',
                        'labelEducation' => 'meta.content.labels.education',
                        'labelExperienceInYears' => 'meta.content.labels.experienceInYears',
                        'labelLanguage' => 'meta.content.labels.language',
                        'labelLanguages' => 'meta.content.labels.languages',
                        'labelMoreCompetences' => 'meta.content.labels.moreCompetences',
                        'labelOverview' => 'meta.content.labels.overview',
                        'labelPageOf' => 'meta.content.label.pageOfs',
                        'labelPage' => 'meta.content.labels.page',
                        'labelProjects' => 'meta.content.labels.projects',
                        'labelSkills' => 'meta.content.labels.skills',
                        'labelYearsPlural' => 'meta.content.label.years.plurals',
                        'labelYearsSingular' => 'meta.content.labels.years.singular',
                        'metaLastModified' => 'meta.lastModified',
                        'metaVersion' => 'meta.version',
                        'basicsOverview' => [
                            0 => [
                                'overviewItemLabel' => 'basic.overview.items.0.label',
                                'overviewItemValue' => 'basic.overview.items.0.value',
                            ],
                            1 => [
                                'overviewItemLabel' => 'basic.overview.items.1.label',
                                'overviewItemValue' => 'basic.overview.items.1.value',
                            ],
                            2 => [
                                'overviewItemLabel' => 'basic.overview.items.*.label',
                                'overviewItemValue' => 'basic.overview.items.*.value',
                            ],
                        ],
                        'basicsProfiles' => [
                            0 => [
                                'basicProfilesNetwork' => 'basic.profiles.0.network',
                                'basicProfilesUrl' => 'basic.profiles.0.url',
                                'basicProfilesUsername' => 'basic.profiles.0.username',
                            ],
                            1 => [
                                'basicProfilesNetwork' => 'basic.profiles.*.network',
                                'basicProfilesUrl' => 'basic.profiles.*.url',
                                'basicProfilesUsername' => 'basic.profiles.*.username',
                            ],
                        ],
                        'educations' => [
                            0 => [
                                'educationArea' => 'education.0.area',
                                'educationEndDate' => 'education.0.endDate',
                                'educationStartDate' => 'education.0.startDate',
                                'educationStudyType' => 'education.0.studyType',
                            ],
                            1 => [
                                'educationArea' => 'education.1.area',
                                'educationEndDate' => 'education.1.endDate',
                                'educationStartDate' => 'education.1.startDate',
                                'educationStudyType' => 'education.1.studyType',
                            ],
                        ],
                        'languages' => [
                            0 => [
                                'languageFluency' => 'languages.*.fluency',
                                'languageName' => 'languages.*.language',
                            ],
                        ],
                        'projects' => [
                            0 => [
                                'projectDescription' => 'projects.0.description',
                                'projectEndDate' => 'projects.0.endDate',
                                'projectName' => 'projects.0.name',
                                'projectStartDate' => 'projects.0.startDate',
                                'projectHighlights' => [
                                    0 => 'projects.0.highlights.0',
                                    1 => 'projects.0.highlights.1',
                                    2 => 'projects.0.highlights.2',
                                    3 => 'projects.0.highlights.*',
                                ],
                                'projectKeywords' => [
                                    0 => 'projects.0.keywords.*',
                                ],
                                'projectRoles' => [
                                    0 => 'projects.0.roles.*',
                                ],
                            ],
                            1 => [
                                'projectDescription' => 'projects.*.description',
                                'projectEndDate' => 'projects.*.endDate',
                                'projectName' => 'projects.*.name',
                                'projectStartDate' => 'projects.*.startDate',
                                'projectHighlights' => [
                                    0 => 'projects.*.highlights.*',
                                ],
                                'projectKeywords' => [
                                    0 => 'projects.*.keywords.*',
                                ],
                                'projectRoles' => [
                                    0 => 'projects.*.roles.*',
                                ],
                            ],
                        ],
                        'skills' => [
                            0 => [
                                'label' => 'skills.0.label',
                                'name' => 'skills.0.name',
                                'keywords' => [
                                    0 => [
                                        'skillKeywordExperienceInYears' => 'skills.0.detailedKeywords.0.experienceInYears',
                                        'skillKeywordLabel' => 'skills.0.detailedKeywords.0.keyword',
                                        'skillKeywordLevel' => 'skills.0.detailedKeywords.0.level',
                                    ],
                                    1 => [
                                        'skillKeywordExperienceInYears' => 'skills.0.detailedKeywords.1.experienceInYears',
                                        'skillKeywordLabel' => 'skills.0.detailedKeywords.1.keyword',
                                        'skillKeywordLevel' => 'skills.0.detailedKeywords.1.level',
                                    ],
                                    2 => [
                                        'skillKeywordExperienceInYears' => 'skills.0.detailedKeywords.*.experienceInYears',
                                        'skillKeywordLabel' => 'skills.0.detailedKeywords.*.keyword',
                                        'skillKeywordLevel' => 'skills.0.detailedKeywords.*.level',
                                    ],
                                ],
                            ],
                            1 => [
                                'label' => 'skills.*.label',
                                'name' => 'skills.*.name',
                                'keywords' => [
                                    0 => [
                                        'skillKeywordExperienceInYears' => 'skills.*.detailedKeywords.*.experienceInYears',
                                        'skillKeywordLabel' => 'skills.*.detailedKeywords.*.keyword',
                                        'skillKeywordLevel' => 'skills.*.detailedKeywords.*.level',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
