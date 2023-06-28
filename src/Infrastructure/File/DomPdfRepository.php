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

        if (isset($settings[DomPdfFont::KEY_FONTS])) {
            foreach ($settings[DomPdfFont::KEY_FONTS] as $font) {
                $domPdfFont = new DomPdfFont(
                    family: $font[DomPdfFont::KEY_FAMILY],
                    style: $font[DomPdfFont::KEY_STYLE],
                    weight: $font[DomPdfFont::KEY_WEIGHT],
                    fontFile: $font[DomPdfFont::KEY_FILE],
                );

                $domPdf = $this->domPdfBuilder->addFont(
                    domPdf: $domPdf,
                    font: $domPdfFont,
                );
            }
        }

        $domPdf = $this->domPdfBuilder->addHtml(domPdf: $domPdf, html: $html);

        if (isset($settings[DomPdfPageText::KEY_PAGE_NUMBERS])) {
            $font = $domPdf->getFontMetrics()->getFont(
                familyRaw: $settings[DomPdfPageText::KEY_PAGE_NUMBERS][DomPdfPageText::KEY_FONT],
            );

            if (empty($font)) {
                throw new \Exception('Unknown font, cant add pageNumbers');
            }

            $pageText = new DomPdfPageText(
                text: $settings[DomPdfPageText::KEY_PAGE_NUMBERS][DomPdfPageText::KEY_TEXT],
                font: $font,
                axisX: $settings[DomPdfPageText::KEY_PAGE_NUMBERS][DomPdfPageText::KEY_AXIS_X],
                axisY: $settings[DomPdfPageText::KEY_PAGE_NUMBERS][DomPdfPageText::KEY_AXIS_Y],
                color: $settings[DomPdfPageText::KEY_PAGE_NUMBERS][DomPdfPageText::KEY_COLOR],
                size: $settings[DomPdfPageText::KEY_PAGE_NUMBERS][DomPdfPageText::KEY_SIZE],
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
