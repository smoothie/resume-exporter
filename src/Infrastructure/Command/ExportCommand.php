<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Command;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Smoothie\ResumeExporter\Application\Resume\ResumeRepository as WriteResumeRepository;
use Smoothie\ResumeExporter\Domain\Mapping\FilesystemRepository;
use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputFormat;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Domain\Resume\ResumeRepository as ReadResumeRepository;
use Smoothie\ResumeExporter\Infrastructure\Constraints\IsPathPdf;
use Smoothie\ResumeExporter\Infrastructure\Constraints\IsPathTwigTemplate;
use Smoothie\ResumeExporter\Infrastructure\Constraints\StringExists;
use Smoothie\ResumeExporter\Infrastructure\DependencyFactory;
use Smoothie\ResumeExporter\Infrastructure\File\JsonNataStrategy;
use Smoothie\ResumeExporter\Infrastructure\File\NoOpRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'resume:export',
    description: 'Convert a JSONResume into another format',
    hidden: false,
)]
class ExportCommand extends Command
{
    protected SymfonyStyle $io;
    protected array $groups = ['json'];

    // todo consider merging inputMap and outputMap

    public const ARGUMENT_INPUT = 'input';
    public const DESCRIPTION_INPUT = 'Configuration file for incoming data';

    public const ARGUMENT_OUTPUT = 'output';
    public const DESCRIPTION_OUTPUT = 'Configuration file for outgoing data';

    public function __construct(
        private readonly FilesystemRepository $filesystem,
        private readonly ValidatorInterface $validator,
        private ?DependencyFactory $dependencyFactory,
        private string $pythonCli,
        private string $pythonJsonataCli,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                name: self::ARGUMENT_INPUT,
                mode: InputArgument::REQUIRED,
                description: self::DESCRIPTION_INPUT,
            )
            ->addArgument(
                name: self::ARGUMENT_OUTPUT,
                mode: InputArgument::OPTIONAL,
                description: self::DESCRIPTION_OUTPUT,
            )
            ->addOption(
                name: 'type',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Map either by "PROPERTY_ACCESSOR" or "JSONATA"',
                default: 'PROPERTY_ACCESSOR',
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $typeParameter = $input->getOption('type');
        if ($this->dependencyFactory === null) {
            throw new \RuntimeException(
                'Unable to initialize, because dependency factory is empty. Initializing without service container is not supported, yet.',
            );
        }

        if (mb_strtoupper($typeParameter) === 'JSONATA') {
            $this->groups = ['jsonata'];
            $this->dependencyFactory->setDefinition(
                MappingStrategy::class,
                fn (): MappingStrategy => new JsonNataStrategy($this->pythonCli, $this->pythonJsonataCli),
            );
            $this->dependencyFactory->setDefinition(
                ReadResumeRepository::class,
                static fn (): ReadResumeRepository => new NoOpRepository(),
            );
        }

        $dependencyFactory = $this->dependencyFactory;

        if ($dependencyFactory->isFrozen()) {
            return;
        }

        $logger = new ConsoleLogger($output);
        $dependencyFactory->setService(LoggerInterface::class, $logger);
        $dependencyFactory->freeze();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->validate();
        $io = $this->io;

        try {
            $inputFile = $input->getArgument(name: self::ARGUMENT_INPUT);
            $outputFile = $input->getArgument(name: self::ARGUMENT_OUTPUT);
            $type = mb_strtoupper($input->getOption('type'));

            $errors = match ($type) {
                'JSONATA' => $this->validate([
                    self::ARGUMENT_OUTPUT => $inputFile,
                ]),
                default => $this->validate([
                    self::ARGUMENT_INPUT => $inputFile,
                    self::ARGUMENT_OUTPUT => $outputFile,
                ]),
            };

            if (! empty($errors)) {
                $this->printErrors(errors: $errors);

                return Command::INVALID;
            }

            if (mb_strtoupper($input->getOption('type')) === 'JSONATA') {
                $output = $this->newOutputFromJsoNata($inputFile);
            } else {
                $input = $this->newInputFromJson($inputFile);
                if ($input === null) {
                    return Command::INVALID;
                }

                $resume = $this->readRepository()->firstAndTranslate($input);

                $output = $this->newOutputFromCanonical($outputFile, $resume);
                if ($output === null) {
                    return Command::INVALID;
                }
            }

            $this->persist($output);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        return Command::SUCCESS;
    }

    private function readRepository(): ReadResumeRepository
    {
        return $this->dependencyFactory->getReadResumeRepository();
    }

    private function writeRepository(): WriteResumeRepository
    {
        return $this->dependencyFactory->getWriteResumeRepository();
    }

    private function newInputFromJson(string $mapSource): ?Input
    {
        $config = $this->filesystem->getJsonContents($mapSource);
        $content = $this->filesystem->getJsonContents(path: $config['file']);

        return new Input(
            inputId: Uuid::uuid4()->toString(),
            inputSource: $config['file'],
            mapSource: $mapSource,
            input: $content,
            map: $config['map'],
        );
    }

    private function newOutputFromJsoNata(string $mapSource): ?Output
    {
        $config = $this->filesystem->getJsonContents($mapSource);

        return new Output(
            mapSource: $mapSource,
            outputPath: $config['file'],
            outputTemplatePath: $config['template'],
            outputFormat: OutputFormat::from($config['format']),
            mapSettings: $config['settings'],
            map: [],
            canonical: [],
        );
    }

    private function newOutputFromCanonical(string $mapSource, Resume $resume): ?Output
    {
        $config = $this->filesystem->getJsonContents($mapSource);

        return new Output(
            mapSource: $mapSource,
            outputPath: $config['file'],
            outputTemplatePath: $config['template'],
            outputFormat: OutputFormat::from($config['format']),
            mapSettings: $config['settings'],
            map: $config['map'],
            canonical: $resume,
        );
    }

    private function persist(Output $outputConfig): void
    {
        $this->writeRepository()->persist(output: $outputConfig);
    }

    private function printErrors(array $errors): void
    {
        foreach ($errors as $path => $nestedErrors) {
            foreach ($nestedErrors as $error) {
                $this->io->error(\sprintf('%1$s: %2$s', $path, $error));
            }
        }
    }

    /**
     * @return (\Stringable|string)[][]
     *
     * @psalm-return array<string, list{\Stringable|string,...}>
     */
    private function translateViolationsIntoArray(
        ConstraintViolationListInterface $violations,
        string $keyPrefix = '',
    ): array {
        $violationAsArray = [];

        foreach ($violations as $constraint) {
            $propertyPath = $constraint->getPropertyPath();
            $violationAsArray[$keyPrefix.$propertyPath][] = $constraint->getMessage();
        }

        return $violationAsArray;
    }

    private function validate(array $inputs): array
    {
        $errors = $this->validateInputs($inputs);
        if (! empty($errors)) {
            return $errors;
        }

        $errors = empty($inputs[self::ARGUMENT_INPUT]) ? [] : $this->validateInputConfig($inputs[self::ARGUMENT_INPUT]);

        return array_merge(
            $errors,
            $this->validateOutputConfig($inputs[self::ARGUMENT_OUTPUT]),
        );
    }

    private function validateInputs(array $inputs): array
    {
        $groups = new Assert\GroupSequence($this->groups);
        $constraints = new Assert\Collection(
            fields: [
                self::ARGUMENT_INPUT => new Assert\File(
                    groups: ['json'],
                    extensions: 'json',
                ),
                self::ARGUMENT_OUTPUT => new Assert\File(
                    groups: ['json', 'jsonata'],
                    extensions: 'json',
                ),
            ],
            groups: ['json', 'jsonata'],
            allowExtraFields: false,
            allowMissingFields: true,
        );

        $violations = $this->validator->validate(value: $inputs, constraints: $constraints, groups: $groups);
        if ($violations->count() === 0) {
            return [];
        }

        return $this->translateViolationsIntoArray(violations: $violations);
    }

    private function validateOutputConfig(string $file): array
    {
        $config = $this->filesystem->getJsonContents($file);
        $groups = new Assert\GroupSequence($this->groups);
        $constraints = new Assert\Collection(
            fields: [
                'file' => new IsPathPdf(['groups' => ['json', 'jsonata']]),
                'template' => new IsPathTwigTemplate(['groups' => ['json', 'jsonata']]),
                'format' => new Assert\Choice(
                    choices: OutputFormat::values(),
                    message: 'The format {{ value }} is not supported. Available formats: {{ choices }}.',
                    groups: ['json', 'jsonata'],
                ),
                'settings' => new Assert\Collection(
                    fields: [
                        'fonts' => new Assert\Optional(
                            new Assert\All([
                                new Assert\Collection(
                                    fields: [
                                        'family' => new StringExists(['groups' => ['json', 'jsonata']]),
                                        'style' => new StringExists(['groups' => ['json', 'jsonata']]),
                                        'weight' => new StringExists(['groups' => ['json', 'jsonata']]),
                                        'fontFile' => new Assert\File(
                                            groups: ['json', 'jsonata'],
                                            extensions: [
                                                'ttf' => [
                                                    'application/x-font-truetype',
                                                    'application/x-font-ttf',
                                                    'application/font-sfnt',
                                                    'font/ttf',
                                                    'font/sfnt',
                                                ],
                                            ],
                                        ),
                                    ],
                                    groups: ['json', 'jsonata'],
                                    allowExtraFields: false,
                                    allowMissingFields: false,
                                ),
                            ]),
                        ),
                        'pageNumbers' => new Assert\Collection(
                            fields: [
                                'text' => new StringExists(['groups' => ['json', 'jsonata']]),
                                'font' => new StringExists(['groups' => ['json', 'jsonata']]),
                                'x' => [
                                    new Assert\Length(min: 0, groups: ['json', 'jsonata']),
                                    new Assert\Type(type: 'int', groups: ['json', 'jsonata']),
                                ],
                                'y' => [
                                    new Assert\Length(min: 0, groups: ['json', 'jsonata']),
                                    new Assert\Type(type: 'int', groups: ['json', 'jsonata']),
                                ],
                                'color' => [
                                    new Assert\Count(min: 3, groups: ['json', 'jsonata']),
                                    new Assert\Type(type: 'array', groups: ['json', 'jsonata']),
                                    new Assert\All(
                                        [
                                            new Assert\Length(min: 0),
                                            new Assert\Type(type: 'float'),
                                        ],
                                        groups: ['json', 'jsonata'],
                                    ),
                                ],
                                'size' => [
                                    new Assert\Length(min: 0, groups: ['json', 'jsonata']),
                                    new Assert\Type(type: 'int', groups: ['json', 'jsonata']),
                                ],
                            ],
                            groups: ['json', 'jsonata'],
                            allowExtraFields: false,
                            allowMissingFields: false,
                        ),
                    ],
                    groups: ['json', 'jsonata'],
                    allowExtraFields: true,
                    allowMissingFields: true,
                ),
                'map' => new Assert\Optional(
                    new Assert\Collection(
                        fields: [],
                        groups: ['json'],
                        allowExtraFields: true,
                        allowMissingFields: true,
                    ),
                ),
            ],
            groups: ['json', 'jsonata'],
            allowExtraFields: false,
            allowMissingFields: false,
        );

        $violations = $this->validator->validate(value: $config, constraints: $constraints, groups: $groups);
        if ($violations->count() === 0) {
            return [];
        }

        return $this->translateViolationsIntoArray(
            violations: $violations,
            keyPrefix: \sprintf(
                '[%1$s]',
                self::ARGUMENT_OUTPUT,
            ),
        );
    }

    private function validateInputConfig(string $input): array
    {
        $config = $this->filesystem->getJsonContents($input);
        $groups = new Assert\GroupSequence($this->groups);
        $constraints = new Assert\Collection(
            fields: [
                'file' => new Assert\File(
                    groups: ['json'],
                    extensions: 'json',
                ),
                'map' => new Assert\Collection(
                    fields: [],
                    groups: ['json'],
                    allowExtraFields: true,
                    allowMissingFields: true,
                ),
            ],
            groups: ['json'],
            allowExtraFields: false,
            allowMissingFields: false,
        );

        $violations = $this->validator->validate(value: $config, constraints: $constraints, groups: $groups);
        if ($violations->count() === 0) {
            return [];
        }

        return $this->translateViolationsIntoArray(
            violations: $violations,
            keyPrefix: \sprintf(
                '[%1$s]',
                self::ARGUMENT_INPUT,
            ),
        );
    }
}
