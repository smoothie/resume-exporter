<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

class DomPdfRepository
{
    public function __construct(
        private readonly FilesystemRepository $filesystem,
        private readonly TwigFactory $twigFactory,
        private readonly DomPdfBuilder $domPdfBuilder,
    ) {
    }

    public function save(string $templateFile, string $outputPath, array $pdfData, array $settings): void
    {
        $templateDirectoryPath = \dirname($templateFile);
        $templateName = basename($templateFile);

        $twig = $this->twigFactory->createFromDirectory($templateDirectoryPath);
        $html = $twig->render(name: $templateName, context: $pdfData);

        $domPdf = $this->domPdfBuilder->start(templateDirectory: $templateDirectoryPath);

        if (isset($settings['fonts'])) {
            foreach ($settings['fonts'] as $font) {
                // todo validate and initialize those very early -> in command probably
                $domPdfFont = new DomPdfFont(
                    family: $font['family'],
                    style: $font['style'],
                    weight: $font['weight'],
                    fontFile: $font['fontFile'],
                );

                $domPdf = $this->domPdfBuilder->addFont(
                    domPdf: $domPdf,
                    font: $domPdfFont,
                );
            }
        }

        $domPdf = $this->domPdfBuilder->addHtml(domPdf: $domPdf, html: $html);

        if (isset($settings['pageNumbers'])) {
            $font = $domPdf->getFontMetrics()->getFont(familyRaw: $settings['pageNumbers']['font']);
            if (empty($font)) {
                throw new \Exception('Unknown font, cant add pageNumbers');
            }

            // todo validate and initialize those very early -> in command probably
            $pageText = new DomPdfPageText(
                text: $settings['pageNumbers']['text'],
                font: $font,
                axisX: $settings['pageNumbers']['x'],
                axisY: $settings['pageNumbers']['y'],
                color: $settings['pageNumbers']['color'],
                size: $settings['pageNumbers']['size'],
            );

            $domPdf = $this->domPdfBuilder->addPageText(domPdf: $domPdf, pageText: $pageText);
        }

        $outputData = $this->domPdfBuilder->print($domPdf);

        $this->filesystem->save(
            outputPath: $outputPath,
            outputData: $outputData,
        );
    }
}
