<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Application\Resume\ResumeFactory as WriteResumeFactory;
use Smoothie\ResumeExporter\Application\Resume\ResumeRepository;
use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputMapping;

class PdfResumeRepository implements ResumeRepository, OutputMapping
{
    public function __construct(
        private readonly MappingStrategy $mappingStrategy,
        private readonly WriteResumeFactory $resumeFactory,
        private readonly DomPdfRepository $domPdfRepository,
    ) {
    }

    public function translateFromCanonical(Output $output): array
    {
        $outputMap = $output->getMap();
        $settings = $output->getMapSettings();
        $resumeData = $this->resumeFactory->toArray($output->getCanonical());
        $this->mappingStrategy->validate(map: $outputMap, from: $resumeData, settings: $settings);
        $map = $this->mappingStrategy->normalize(map: $outputMap, from: $resumeData, settings: $settings);
        $pdfData = $this->mappingStrategy->translate(map: $map, from: $resumeData, settings: $settings);

        return $pdfData;
    }

    public function persist(Output $output): void
    {
        $pdfData = $this->translateFromCanonical($output);
        $templateFilePath = $output->outputTemplatePath();
        $outputPath = $output->outputPath();
        $settings = $output->getMapSettings();

        $this->domPdfRepository->save(
            templateFile: $templateFilePath,
            outputPath: $outputPath,
            pdfData: $pdfData,
            settings: $settings,
        );
    }
}
