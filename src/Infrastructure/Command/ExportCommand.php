<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Command;

use Ramsey\Uuid\Uuid;
use Smoothie\ResumeExporter\Domain\Mapping\FilesystemRepository;
use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputFormat;
use Smoothie\ResumeExporter\Infrastructure\Constraints\IsPathPdf;
use Smoothie\ResumeExporter\Infrastructure\Constraints\IsPathTwigTemplate;
use Smoothie\ResumeExporter\Infrastructure\Constraints\StringExists;
use Smoothie\ResumeExporter\Infrastructure\File\JsonResumeRepository;
use Smoothie\ResumeExporter\Infrastructure\File\PdfResumeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
    // todo consider merging inputMap and outputMap

    public const ARGUMENT_INPUT = 'input';
    public const DESCRIPTION_INPUT = 'Configuration file for incoming data';

    public const ARGUMENT_OUTPUT = 'output';
    public const DESCRIPTION_OUTPUT = 'Configuration file for outgoing data';

    public function __construct(
        private readonly FilesystemRepository $filesystem,
        private readonly JsonResumeRepository $jsonResumeRepository,
        private readonly PdfResumeRepository $pdfResumeRepository,
        private readonly ValidatorInterface $validator,
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
                mode: InputArgument::REQUIRED,
                description: self::DESCRIPTION_OUTPUT,
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->validate();
        $io = new SymfonyStyle(input: $input, output: $output);

        try {
            $inputFile = $input->getArgument(name: self::ARGUMENT_INPUT);
            $outputFile = $input->getArgument(name: self::ARGUMENT_OUTPUT);

            $errors = $this->validateInputs([
                self::ARGUMENT_INPUT => $inputFile,
                self::ARGUMENT_OUTPUT => $outputFile,
            ]);

            if (! empty($errors)) {
                $this->printErrors(io: $io, errors: $errors);

                return Command::INVALID;
            }

            $inputConfig = $this->filesystem->getJsonContents(path: $inputFile);
            $outputConfig = $this->filesystem->getJsonContents(path: $outputFile);

            $errors = $this->validateInputConfig($inputConfig);
            $errors += $this->validateOutputConfig($outputConfig);
            if (! empty($errors)) {
                $this->printErrors(io: $io, errors: $errors);

                return Command::INVALID;
            }

            $inputContent = $this->filesystem->getJsonContents(path: $inputConfig['file']);
            $inputConfig = new Input(
                inputId: Uuid::uuid4()->toString(),
                inputSource: $inputConfig['file'],
                mapSource: $inputFile,
                input: $inputContent,
                map: $inputConfig['map'],
            );

            $resume = $this->jsonResumeRepository->firstAndTranslate(input: $inputConfig);
            $outputConfig = new Output(
                mapSource: $outputFile,
                outputPath: $outputConfig['file'],
                outputTemplatePath: $outputConfig['template'],
                outputFormat: OutputFormat::from($outputConfig['format']),
                mapSettings: $outputConfig['settings'],
                map: $outputConfig['map'],
                canonical: $resume,
            );

            $this->pdfResumeRepository->persist(output: $outputConfig);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        return Command::SUCCESS;
    }

    private function printErrors(SymfonyStyle $io, array $errors): void
    {
        foreach ($errors as $path => $nestedErrors) {
            foreach ($nestedErrors as $error) {
                $io->error(sprintf('%1$s: %2$s', $path, $error));
            }
        }
    }

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

    private function validateInputs(array $inputs): array
    {
        $groups = new Assert\GroupSequence(['Default']);
        $constraints = new Assert\Collection(
            fields: [
                self::ARGUMENT_INPUT => new Assert\File(
                    extensions: 'json',
                ),
                self::ARGUMENT_OUTPUT => new Assert\File(
                    extensions: 'json',
                ),
            ],
            allowExtraFields: false,
            allowMissingFields: false,
        );

        $violations = $this->validator->validate(value: $inputs, constraints: $constraints, groups: $groups);
        if ($violations->count() === 0) {
            return [];
        }

        return $this->translateViolationsIntoArray(violations: $violations);
    }

    private function validateOutputConfig(array $outputConfig): array
    {
        $groups = new Assert\GroupSequence(['Default']);
        $constraints = new Assert\Collection(
            fields: [
                'file' => new IsPathPdf(),
                'template' => new IsPathTwigTemplate(),
                'format' => new Assert\Choice(
                    choices: OutputFormat::values(),
                    message: 'The format {{ value }} is not supported. Available formats: {{ choices }}.',
                ),
                'settings' => new Assert\Collection(
                    fields: [
                        'fonts' => new Assert\Optional(
                            new Assert\All([
                                new Assert\Collection(
                                    fields: [
                                        'family' => new StringExists(),
                                        'style' => new StringExists(),
                                        'weight' => new StringExists(),
                                        'fontFile' => new Assert\File(extensions: [
                                            'ttf' => [
                                                'application/x-font-truetype',
                                                'application/x-font-ttf',
                                                'application/font-sfnt',
                                                'font/ttf',
                                                'font/sfnt',
                                            ],
                                        ]),
                                    ],
                                    allowExtraFields: false,
                                    allowMissingFields: false,
                                ),
                            ]),
                        ),
                        'pageNumbers' => new Assert\Collection(
                            fields: [
                                'text' => new StringExists(),
                                'font' => new StringExists(),
                                'x' => [
                                    new Assert\Length(min: 0),
                                    new Assert\Type(type: 'int'),
                                ],
                                'y' => [
                                    new Assert\Length(min: 0),
                                    new Assert\Type(type: 'int'),
                                ],
                                'color' => [
                                    new Assert\Count(min: 3),
                                    new Assert\Type(type: 'array'),
                                    new Assert\All(
                                        [
                                            new Assert\Length(min: 0),
                                            new Assert\Type(type: 'float'),
                                        ],
                                    ),
                                ],
                                'size' => [
                                    new Assert\Length(min: 0),
                                    new Assert\Type(type: 'int'),
                                ],
                            ],
                            allowExtraFields: false,
                            allowMissingFields: false,
                        ),
                    ],
                    allowExtraFields: true,
                    allowMissingFields: true,
                ),
                'map' => new Assert\Collection(
                    fields: [],
                    allowExtraFields: true,
                    allowMissingFields: true,
                ),
            ],
            allowExtraFields: false,
            allowMissingFields: false,
        );

        $violations = $this->validator->validate(value: $outputConfig, constraints: $constraints, groups: $groups);
        if ($violations->count() === 0) {
            return [];
        }

        return $this->translateViolationsIntoArray(
            violations: $violations,
            keyPrefix: sprintf(
                '[%1$s]',
                self::ARGUMENT_OUTPUT,
            ),
        );
    }

    private function validateInputConfig(array $inputConfig): array
    {
        $groups = new Assert\GroupSequence(['Default']);
        $constraints = new Assert\Collection(
            fields: [
                'file' => new Assert\File(
                    extensions: 'json',
                ),
                'map' => new Assert\Collection(
                    fields: [],
                    allowExtraFields: true,
                    allowMissingFields: true,
                ),
            ],
            allowExtraFields: false,
            allowMissingFields: false,
        );

        $violations = $this->validator->validate(value: $inputConfig, constraints: $constraints, groups: $groups);
        if ($violations->count() === 0) {
            return [];
        }

        return $this->translateViolationsIntoArray(
            violations: $violations,
            keyPrefix: sprintf(
                '[%1$s]',
                self::ARGUMENT_INPUT,
            ),
        );
    }
}
