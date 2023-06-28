<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Acceptance\Command;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Pdfbox\Processor\PdfFile;
use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputFormat;
use Smoothie\ResumeExporter\Infrastructure\Command\ExportCommand;
use Smoothie\Tests\ResumeExporter\BasicKernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group acceptance
 * @group acceptance-cli
 * @group acceptance-cli-export-resume
 */
class ExportCommandTest extends BasicKernelTestCase
{
    private vfsStreamDirectory|null $mockedTmpDir = null;
    private PdfFile|null $pdfbox = null;

    protected function setUp(): void
    {
        $this->mockedTmpDir = vfsStream::setup('mockedTmp');
        $this->pdfbox = $this->buildPdfBox();
    }

    /**
     * @dataProvider provideNotSoGoodPathJsonResumeToTwigPdf
     */
    public function testWillThrowHardWhenConvertingAJsonResumeIntoAPdfByATwigTemplate(
        array $assertions,
        array $expectations,
    ): void {
        static::assertNotNull(
            actual: $this->mockedTmpDir,
            message: 'Unable to test, virtual directories have not been initialized',
        );

        if (! empty($assertions['inputResumeFileName'])) {
            vfsStream::newFile($assertions['inputResumeFileName'])
                ->at($this->mockedTmpDir)
                ->setContent($assertions['inputResume']);
        }

        if (! empty($assertions['inputConfigFileName'])) {
            vfsStream::newFile($assertions['inputConfigFileName'])
                ->at($this->mockedTmpDir)
                ->setContent($assertions['input']);
        }

        if (! empty($assertions['outputConfigFileName'])) {
            vfsStream::newFile($assertions['outputConfigFileName'])
                ->at($this->mockedTmpDir)
                ->setContent($assertions['output']);
        }

        // when a template is larger than 1mb this won't work see vfsStream mocking large files
        vfsStream::copyFromFileSystem(path: $assertions['templateDirectory'], baseDir: $this->mockedTmpDir);
        if (! empty($assertions['fontsDirectory'])) {
            vfsStream::copyFromFileSystem(path: $assertions['fontsDirectory'], baseDir: $this->mockedTmpDir);
        }

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('resume:export');
        $commandTester = new CommandTester($command);
        $commandTester->execute(input: [
            ExportCommand::ARGUMENT_INPUT => $assertions['inputConfigFilePath'],
            ExportCommand::ARGUMENT_OUTPUT => $assertions['outputConfigFilePath'],
        ]);

        // yeah ugly but .. https://github.com/symfony/symfony/discussions/42682
        $output = str_replace(\PHP_EOL, '', $commandTester->getDisplay());
        $output = preg_replace('/  +/', ' ', $output);
        $output = trim($output);
        static::assertSame($expectations['statusCode'], $commandTester->getStatusCode());
        static::assertSame($expectations['errorMessage'], $output);
    }

    public function provideNotSoGoodPathJsonResumeToTwigPdf(): array
    {
        // todo

        return [
            'when_input_config_is_not_defined' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
//                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [input]: The file could not be found.
                        BAUM,
                ],
            ],
            'when_input_config_has_no_valid_json_contents' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => serialize([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [input]: The mime type of the file is invalid ("text/plain"). Allowed mime types are "application/json", "application/schema+json".
                        BAUM,
                ],
            ],
            'when_input_config_is_not_a_json_file' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.md',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.md',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [input]: The extension of the file is invalid ("md"). Allowed extensions are "json".
                        BAUM,
                ],
            ],
            'when_output_config_has_no_valid_json_contents' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => serialize([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output]: The mime type of the file is invalid ("text/plain"). Allowed mime types are "application/json", "application/schema+json".
                        BAUM,
                ],
            ],
            'when_output_config_is_not_a_json_file' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.woot',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.woot',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output]: The extension of the file is invalid ("woot"). Allowed extensions are "json".
                        BAUM,
                ],
            ],
            'when_output_config_is_not_defined' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
//                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output]: The file could not be found.
                        BAUM,
                ],
            ],
            'when_input_config_file_is_not_defined' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
//                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [input][file]: This field is missing.
                        BAUM,
                ],
            ],
            'when_input_config_file_is_not_json' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.woot',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.woot',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [input][file]: The extension of the file is invalid ("woot"). Allowed extensions are "json".
                        BAUM,
                ],
            ],
            'when_input_config_file_does_not_exist' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.woot',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [input][file]: The file could not be found.
                        BAUM,
                ],
            ],
            'when_input_config_map_is_not_defined' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
//                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [input][map]: This field is missing.
                        BAUM,
                ],
            ],
            'when_output_config_file_is_not_defined' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
//                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][file]: This field is missing.
                        BAUM,
                ],
            ],
            'when_output_config_file_is_not_a_pdf' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.woot',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][file]: The path "vfs://mockedTmp/good-json-to-twig-pdf__output-1.woot" must end with extension ".pdf".
                        BAUM,
                ],
            ],
            'when_output_config_template_is_not_defined' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
//                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][template]: This field is missing.
                        BAUM,
                ],
            ],
            'when_output_config_template_is_not_a_twig_template' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.woot',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][template]: The path "vfs://mockedTmp/template.html.woot" must end with extension ".twig".
                        BAUM,
                ],
            ],
            'when_output_config_format_is_not_defined' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
//                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][format]: This field is missing.
                        BAUM,
                ],
            ],
            'when_output_config_format_is_not_a_pdf' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => 'woot',
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][format]: The format "woot" is not supported. Available formats: "PDF", "DOCTRINE".
                        BAUM,
                ],
            ],
            'when_output_config_1_font_has_not_all_required_fields' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [
                            'fonts' => [
                                0 => [],
                            ],
                        ],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][settings][fonts][0][family]: This field is missing. [ERROR] [output][settings][fonts][0][style]: This field is missing. [ERROR] [output][settings][fonts][0][weight]: This field is missing. [ERROR] [output][settings][fonts][0][fontFile]: This field is missing.
                        BAUM,
                ],
            ],
            'when_output_config_1_font_file_does_not_exist' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [
                            'fonts' => [
                                0 => [
                                    'family' => 'Poppins',
                                    'style' => 'normal',
                                    'weight' => 'normal',
                                    'fontFile' => 'vfs://mockedTmp/Poppins-Regular.ttf',
                                ],
                            ],
                        ],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][settings][fonts][0][fontFile]: The file could not be found.
                        BAUM,
                ],
            ],
            'when_output_config_page_number_has_not_all_required_fields' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
                        'map' => [],
                    ]),
                    'output' => json_encode([
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [
                            'pageNumbers' => [],
                        ],
                        'map' => [],
                    ]),
                ],
                'expectations' => [
                    'statusCode' => 2,
                    'errorMessage' => <<<BAUM
                        [ERROR] [output][settings][pageNumbers][text]: This field is missing. [ERROR] [output][settings][pageNumbers][font]: This field is missing. [ERROR] [output][settings][pageNumbers][x]: This field is missing. [ERROR] [output][settings][pageNumbers][y]: This field is missing. [ERROR] [output][settings][pageNumbers][color]: This field is missing. [ERROR] [output][settings][pageNumbers][size]: This field is missing.
                        BAUM,
                ],
            ],
        ];
    }

    /**
     * @group acceptance-cli-export-resume-good-path
     *
     * @dataProvider provideGoodPathJsonResumeToTwigPdf
     */
    public function testWillConvertAJsonResumeIntoAPdfByATwigTemplate(array $assertions, array $expectations): void
    {
        static::assertNotNull(
            actual: $this->mockedTmpDir,
            message: 'Unable to test, virtual directories have not been initialized',
        );

        static::assertNotNull(
            actual: $this->pdfbox,
            message: 'PDFBox not initialized - unable to process test',
        );

        $input = json_encode($assertions['input']);
        $output = json_encode($assertions['output']);

        vfsStream::newFile($assertions['inputResumeFileName'])
            ->at($this->mockedTmpDir)
            ->setContent($assertions['inputResume']);

        vfsStream::newFile($assertions['inputConfigFileName'])
            ->at($this->mockedTmpDir)
            ->setContent($input);

        vfsStream::newFile($assertions['outputConfigFileName'])
            ->at($this->mockedTmpDir)
            ->setContent($output);

        // when a template is larger than 1mb this won't work see vfsStream mocking large files
        vfsStream::copyFromFileSystem(path: $assertions['templateDirectory'], baseDir: $this->mockedTmpDir);
        if (! empty($assertions['fontsDirectory'])) {
            vfsStream::copyFromFileSystem(path: $assertions['fontsDirectory'], baseDir: $this->mockedTmpDir);
        }

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('resume:export');
        $commandTester = new CommandTester($command);
        $commandTester->execute(input: [
            'input' => $assertions['inputConfigFilePath'],
            'output' => $assertions['outputConfigFilePath'],
        ]);

        $commandTester->assertCommandIsSuccessful();

        $result = $this->mockedTmpDir->getChild($expectations['file']);
        static::assertNotNull(actual: $result, message: 'PDF was not found on virtual directory.');

        $tmpFile = $this->getTmpDirectory($assertions['outputResumeFileName']);
        if (is_readable($tmpFile)) {
            unlink($tmpFile);
        }

        file_put_contents(
            $tmpFile,
            file_get_contents($result->url()),
        );

        $resultContent = [];

        try {
            $resultContent = $this->pdfbox->toHtml($tmpFile);
        } catch (\Throwable $exception) {
        }

        static::assertSame(expected: $expectations['contents'], actual: $resultContent);

        unlink($tmpFile);
    }

    public function provideGoodPathJsonResumeToTwigPdf(): array
    {
        return [
            'simple_no_settings' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-1.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-1.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-1.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-1.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-1.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => [
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-1.json',
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
                    'output' => [
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-1.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [],
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
                            '[Meta][Content][Labels][ExperienceLevel]' => '[labelExperienceLevel]',
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
                            '[Skills][*][DetailedKeywords][*][ExperienceLevel]' => '[skills][*][keywords][*][skillKeywordExperienceLevel]',
                            '[Skills][*][DetailedKeywords][*][Keyword]' => '[skills][*][keywords][*][skillKeywordLabel]',
                            '[Skills][*][DetailedKeywords][*][Level]' => '[skills][*][keywords][*][skillKeywordLevel]',
                            '[Skills][*][Label]' => '[skills][*][label]',
                            '[Skills][*][Name]' => '[skills][*][name]',
                        ],
                    ],
                ],
                'expectations' => [
                    'file' => 'good-json-to-twig-pdf__output-1.pdf',
                    'contents' => <<<HTML
                        <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
                        "http://www.w3.org/TR/html4/loose.dtd">
                        <html><head><title>Document</title>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        </head>
                        <body>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>meta.content.labels.overview
                        </b></p>
                        <p><b>basic.label
                        </b>basic.summary
                        </p>
                        <p><b>basic.overview.items.0.label
                        </b>basic.overview.items.0.value
                        </p>
                        <p><b>basic.overview.items.1.label
                        </b>basic.overview.items.1.value
                        </p>
                        <p><b>basic.overview.items.*.label
                        </b>basic.overview.items.*.value
                        </p>
                        <p><b>basic.profiles.0.network
                        </b>basic.profiles.0.username
                        </p>
                        <p><b>basic.profiles.*.network
                        </b>basic.profiles.*.username
                        </p>
                        <p>basic.nameImage not found or type unknown <b>basic.name
                        </b>basic.location.address
                        </p>
                        <p>basic.location.postalCode basic.location.city
                        basic.email
                        </p>
                        <p>basic.phone</p>

                        </div></div>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>meta.content.labels.projects
                        </b></p>
                        <p>projects.0.startDate
                        -
                        projects.0.endDate
                        </p>
                        <p><b>projects.0.name
                        </b>projects.0.description
                        </p>
                        <p>projects.0.highlights.0
                        </p>
                        <p>projects.0.highlights.1
                        </p>
                        <p>projects.0.highlights.2
                        </p>
                        <p>projects.0.highlights.*
                        </p>
                        <p><b>meta.content.labels.skills
                        </b>projects.0.keywords.*
                        </p>
                        <p>projects.*.startDate
                        -
                        projects.*.endDate
                        </p>
                        <p><b>projects.*.name
                        </b>projects.*.description
                        </p>
                        <p>projects.*.highlights.*
                        </p>
                        <p><b>meta.content.labels.skills
                        </b>projects.*.keywords.*
                        </p>
                        <p><b>meta.content.labels.education
                        </b></p>
                        <p>education.0.startDate
                        -
                        education.0.endDate
                        </p>
                        <p><b>education.0.area
                        </b>education.0.studyType
                        </p>
                        <p>education.1.startDate
                        -
                        education.1.endDate
                        </p>
                        <p><b>education.1.area
                        </b>education.1.studyType
                        </p>
                        <p><b>meta.content.labels.languages
                        </b></p>
                        <p><b>meta.content.labels.language meta.content.labels.experienceInYears
                        </b></p>
                        <p>languages.*.language languages.*.fluency</p>

                        </div></div>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>skills.0.name
                        </b></p>
                        <p><b>skills.0.label meta.content.labels.experienceInYears meta.content.labels.experienceLevel
                        </b></p>
                        <p>skills.0.detailedKeywords.0.keyword skills.0.detailedKeywords.0.experienceInYears skills.0.detailedKeywords.0.level
                        </p>
                        <p>skills.0.detailedKeywords.1.keyword skills.0.detailedKeywords.1.experienceInYears skills.0.detailedKeywords.1.level
                        </p>
                        <p>skills.0.detailedKeywords.*.keyword skills.0.detailedKeywords.*.experienceInYears skills.0.detailedKeywords.*.level
                        </p>
                        <p><b>skills.*.name
                        </b></p>
                        <p><b>skills.*._label meta.content.labels.experienceInYears meta.content.labels.experienceLevel
                        </b></p>
                        <p>skills.*.detailedKeywords.*.keyword skills.*.detailedKeywords.*.experienceInYears skills.*.detailedKeywords.*.level</p>

                        </div></div>
                        </body></html>
                        HTML,
                ],
            ],
            'with_two_fonts' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-2.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-2.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-2.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-2.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-2.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-2.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => $this->getTemplateDoublesDirectory(path: 'Fonts/Poppins'),
                    'input' => [
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-2.json',
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
                    'output' => [
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-2.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [
                            'fonts' => [
                                0 => [
                                    'family' => 'Poppins',
                                    'style' => 'normal',
                                    'weight' => 'normal',
                                    'fontFile' => 'vfs://mockedTmp/Poppins-Regular.ttf',
                                ],
                                1 => [
                                    'family' => 'Poppins',
                                    'style' => 'italic',
                                    'weight' => 'normal',
                                    'fontFile' => 'vfs://mockedTmp/Poppins-Italic.ttf',
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
                            '[Meta][Content][Labels][ExperienceLevel]' => '[labelExperienceLevel]',
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
                            '[Skills][*][DetailedKeywords][*][ExperienceLevel]' => '[skills][*][keywords][*][skillKeywordExperienceLevel]',
                            '[Skills][*][DetailedKeywords][*][Keyword]' => '[skills][*][keywords][*][skillKeywordLabel]',
                            '[Skills][*][DetailedKeywords][*][Level]' => '[skills][*][keywords][*][skillKeywordLevel]',
                            '[Skills][*][Label]' => '[skills][*][label]',
                            '[Skills][*][Name]' => '[skills][*][name]',
                        ],
                    ],
                ],
                'expectations' => [
                    'file' => 'good-json-to-twig-pdf__output-2.pdf',
                    'contents' => <<<HTML
                        <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
                        "http://www.w3.org/TR/html4/loose.dtd">
                        <html><head><title>Document</title>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        </head>
                        <body>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>meta.content.labels.overview
                        </b></p>
                        <p><b>basic.label
                        </b>basic.summary
                        </p>
                        <p><b>basic.overview.items.0.label
                        </b>basic.overview.items.0.value
                        </p>
                        <p><b>basic.overview.items.1.label
                        </b>basic.overview.items.1.value
                        </p>
                        <p><b>basic.overview.items.*.label
                        </b>basic.overview.items.*.value
                        </p>
                        <p><b>basic.profiles.0.network
                        </b>basic.profiles.0.username
                        </p>
                        <p><b>basic.profiles.*.network
                        </b>basic.profiles.*.username
                        </p>
                        <p>basic.nameImage not found or type unknown <b>basic.name
                        </b>basic.location.address
                        </p>
                        <p>basic.location.postalCode basic.location.city
                        basic.email
                        </p>
                        <p>basic.phone</p>

                        </div></div>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>meta.content.labels.projects
                        </b></p>
                        <p>projects.0.startDate
                        -
                        projects.0.endDate
                        </p>
                        <p><b>projects.0.name
                        </b>projects.0.description
                        </p>
                        <p>projects.0.highlights.0
                        </p>
                        <p>projects.0.highlights.1
                        </p>
                        <p>projects.0.highlights.2
                        </p>
                        <p>projects.0.highlights.*
                        </p>
                        <p><b>meta.content.labels.skills
                        </b>projects.0.keywords.*
                        </p>
                        <p>projects.*.startDate
                        -
                        projects.*.endDate
                        </p>
                        <p><b>projects.*.name
                        </b>projects.*.description
                        </p>
                        <p>projects.*.highlights.*
                        </p>
                        <p><b>meta.content.labels.skills
                        </b>projects.*.keywords.*
                        </p>
                        <p><b>meta.content.labels.education
                        </b></p>
                        <p>education.0.startDate
                        -
                        education.0.endDate
                        </p>
                        <p><b>education.0.area
                        </b>education.0.studyType
                        </p>
                        <p>education.1.startDate
                        -
                        education.1.endDate
                        </p>
                        <p><b>education.1.area
                        </b>education.1.studyType
                        </p>
                        <p><b>meta.content.labels.languages
                        </b></p>
                        <p><b>meta.content.labels.language meta.content.labels.experienceInYears
                        </b></p>
                        <p>languages.*.language languages.*.fluency</p>

                        </div></div>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>skills.0.name
                        </b></p>
                        <p><b>skills.0.label meta.content.labels.experienceInYears meta.content.labels.experienceLevel
                        </b></p>
                        <p>skills.0.detailedKeywords.0.keyword skills.0.detailedKeywords.0.experienceInYears skills.0.detailedKeywords.0.level
                        </p>
                        <p>skills.0.detailedKeywords.1.keyword skills.0.detailedKeywords.1.experienceInYears skills.0.detailedKeywords.1.level
                        </p>
                        <p>skills.0.detailedKeywords.*.keyword skills.0.detailedKeywords.*.experienceInYears skills.0.detailedKeywords.*.level
                        </p>
                        <p><b>skills.*.name
                        </b></p>
                        <p><b>skills.*._label meta.content.labels.experienceInYears meta.content.labels.experienceLevel
                        </b></p>
                        <p>skills.*.detailedKeywords.*.keyword skills.*.detailedKeywords.*.experienceInYears skills.*.detailedKeywords.*.level</p>

                        </div></div>
                        </body></html>
                        HTML,
                ],
            ],
            'with_page_numbers' => [
                'assertions' => [
                    'inputResume' => '{"$schema":"../schemas/resume-schema.json","basics":{"name":"basic.name","label":"basic.label","email":"basic.email","phone":"basic.phone","url":"basic.url","summary":"basic.summary","location":{"address":"basic.location.address","postalCode":"basic.location.postalCode","city":"basic.location.city","countryCode":"basic.location.countryCode"},"profiles":[{"network":"basic.profiles.0.network","username":"basic.profiles.0.username","url":"basic.profiles.0.url"},{"network":"basic.profiles.*.network","username":"basic.profiles.*.username","url":"basic.profiles.*.url"}],"_overview":{"items":[{"label":"basic.overview.items.0.label","value":"basic.overview.items.0.value"},{"label":"basic.overview.items.1.label","value":"basic.overview.items.1.value"},{"label":"basic.overview.items.*.label","value":"basic.overview.items.*.value"}]}},"education":[{"area":"education.0.area","endDate":"education.0.endDate","startDate":"education.0.startDate","studyType":"education.0.studyType"},{"area":"education.1.area","endDate":"education.1.endDate","startDate":"education.1.startDate","studyType":"education.1.studyType"}],"skills":[{"name":"skills.0.name","_label":"skills.0.label","_detailedKeywords":[{"keyword":"skills.0.detailedKeywords.0.keyword","level":"skills.0.detailedKeywords.0.level","experienceInYears":"skills.0.detailedKeywords.0.experienceInYears"},{"keyword":"skills.0.detailedKeywords.1.keyword","level":"skills.0.detailedKeywords.1.level","experienceInYears":"skills.0.detailedKeywords.1.experienceInYears"},{"keyword":"skills.0.detailedKeywords.*.keyword","level":"skills.0.detailedKeywords.*.level","experienceInYears":"skills.0.detailedKeywords.*.experienceInYears"}]},{"name":"skills.*.name","_label":"skills.*._label","_detailedKeywords":[{"keyword":"skills.*.detailedKeywords.*.keyword","level":"skills.*.detailedKeywords.*.level","experienceInYears":"skills.*.detailedKeywords.*.experienceInYears"}]}],"languages":[{"language":"languages.*.language","fluency":"languages.*.fluency"}],"projects":[{"name":"projects.0.name","description":"projects.0.description","entity":"projects.0.entity","type":"projects.0.type","startDate":"projects.0.startDate","endDate":"projects.0.endDate","highlights":["projects.0.highlights.0","projects.0.highlights.1","projects.0.highlights.2","projects.0.highlights.*"],"keywords":["projects.0.keywords.*"],"roles":["projects.0.roles.*"]},{"name":"projects.*.name","description":"projects.*.description","entity":"projects.*.entity","type":"projects.*.type","startDate":"projects.*.startDate","endDate":"projects.*.endDate","highlights":["projects.*.highlights.*"],"keywords":["projects.*.keywords.*"],"roles":["projects.*.roles.*"]}],"meta":{"canonical":"meta.canonical","version":"meta.version","lastModified":"meta.lastModified","_content":{"labels":{"skills":"meta.content.labels.skills","languages":"meta.content.labels.languages","language":"meta.content.labels.language","overview":"meta.content.labels.overview","projects":"meta.content.labels.projects","education":"meta.content.labels.education","competences":"meta.content.labels.competences","moreCompetences":"meta.content.labels.moreCompetences","experienceInYears":"meta.content.labels.experienceInYears","experienceLevel":"meta.content.labels.experienceLevel","years":{"singular":"meta.content.labels.years.singular","plural":"meta.content.label.years.plurals"},"page":"meta.content.labels.page","pageOf":"meta.content.label.pageOfs"}}}}',
                    'inputResumeFileName' => 'good-json-to-twig-pdf__input-3.json',
                    'outputResumeFileName' => 'good-json-to-twig-pdf__output-3.pdf',
                    'inputConfigFileName' => 'good-json-to-twig-pdf__input-config-3.json',
                    'outputConfigFileName' => 'good-json-to-twig-pdf__output-config-3.json',
                    'inputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-config-3.json',
                    'outputConfigFilePath' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-config-3.json',
                    'templateDirectory' => $this->getTemplateDoublesDirectory(path: 'TwigPdf'),
                    'fontsDirectory' => null,
                    'input' => [
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__input-3.json',
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
                    'output' => [
                        'file' => 'vfs://mockedTmp/good-json-to-twig-pdf__output-3.pdf',
                        'format' => OutputFormat::PDF,
                        'template' => 'vfs://mockedTmp/template.html.twig',
                        'settings' => [
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
                            '[Meta][Content][Labels][ExperienceLevel]' => '[labelExperienceLevel]',
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
                            '[Skills][*][DetailedKeywords][*][ExperienceLevel]' => '[skills][*][keywords][*][skillKeywordExperienceLevel]',
                            '[Skills][*][DetailedKeywords][*][Keyword]' => '[skills][*][keywords][*][skillKeywordLabel]',
                            '[Skills][*][DetailedKeywords][*][Level]' => '[skills][*][keywords][*][skillKeywordLevel]',
                            '[Skills][*][Label]' => '[skills][*][label]',
                            '[Skills][*][Name]' => '[skills][*][name]',
                        ],
                    ],
                ],
                'expectations' => [
                    'file' => 'good-json-to-twig-pdf__output-3.pdf',
                    'contents' => <<<HTML
                        <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
                        "http://www.w3.org/TR/html4/loose.dtd">
                        <html><head><title>Document</title>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        </head>
                        <body>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>meta.content.labels.overview
                        </b></p>
                        <p><b>basic.label
                        </b>basic.summary
                        </p>
                        <p><b>basic.overview.items.0.label
                        </b>basic.overview.items.0.value
                        </p>
                        <p><b>basic.overview.items.1.label
                        </b>basic.overview.items.1.value
                        </p>
                        <p><b>basic.overview.items.*.label
                        </b>basic.overview.items.*.value
                        </p>
                        <p><b>basic.profiles.0.network
                        </b>basic.profiles.0.username
                        </p>
                        <p><b>basic.profiles.*.network
                        </b>basic.profiles.*.username
                        </p>
                        <p>basic.nameImage not found or type unknown <b>basic.name
                        </b>basic.location.address
                        </p>
                        <p>basic.location.postalCode basic.location.city
                        basic.email
                        </p>
                        <p>basic.phone
                        </p>
                        <p>Page 1 of 3</p>

                        </div></div>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>meta.content.labels.projects
                        </b></p>
                        <p>projects.0.startDate
                        -
                        projects.0.endDate
                        </p>
                        <p><b>projects.0.name
                        </b>projects.0.description
                        </p>
                        <p>projects.0.highlights.0
                        </p>
                        <p>projects.0.highlights.1
                        </p>
                        <p>projects.0.highlights.2
                        </p>
                        <p>projects.0.highlights.*
                        </p>
                        <p><b>meta.content.labels.skills
                        </b>projects.0.keywords.*
                        </p>
                        <p>projects.*.startDate
                        -
                        projects.*.endDate
                        </p>
                        <p><b>projects.*.name
                        </b>projects.*.description
                        </p>
                        <p>projects.*.highlights.*
                        </p>
                        <p><b>meta.content.labels.skills
                        </b>projects.*.keywords.*
                        </p>
                        <p><b>meta.content.labels.education
                        </b></p>
                        <p>education.0.startDate
                        -
                        education.0.endDate
                        </p>
                        <p><b>education.0.area
                        </b>education.0.studyType
                        </p>
                        <p>education.1.startDate
                        -
                        education.1.endDate
                        </p>
                        <p><b>education.1.area
                        </b>education.1.studyType
                        </p>
                        <p><b>meta.content.labels.languages
                        </b></p>
                        <p><b>meta.content.labels.language meta.content.labels.experienceInYears
                        </b></p>
                        <p>languages.*.language languages.*.fluency
                        </p>
                        <p>Page 2 of 3</p>

                        </div></div>
                        <div style="page-break-before:always; page-break-after:always"><div><p><b>skills.0.name
                        </b></p>
                        <p><b>skills.0.label meta.content.labels.experienceInYears meta.content.labels.experienceLevel
                        </b></p>
                        <p>skills.0.detailedKeywords.0.keyword skills.0.detailedKeywords.0.experienceInYears skills.0.detailedKeywords.0.level
                        </p>
                        <p>skills.0.detailedKeywords.1.keyword skills.0.detailedKeywords.1.experienceInYears skills.0.detailedKeywords.1.level
                        </p>
                        <p>skills.0.detailedKeywords.*.keyword skills.0.detailedKeywords.*.experienceInYears skills.0.detailedKeywords.*.level
                        </p>
                        <p><b>skills.*.name
                        </b></p>
                        <p><b>skills.*._label meta.content.labels.experienceInYears meta.content.labels.experienceLevel
                        </b></p>
                        <p>skills.*.detailedKeywords.*.keyword skills.*.detailedKeywords.*.experienceInYears skills.*.detailedKeywords.*.level
                        </p>
                        <p>Page 3 of 3</p>

                        </div></div>
                        </body></html>
                        HTML,
                ],
            ],
        ];
    }
}
