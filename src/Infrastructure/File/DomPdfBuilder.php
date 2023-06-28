<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;

class DomPdfBuilder
{
    public function start(string $templateDirectory): Dompdf
    {
        $tmpDirectory = sys_get_temp_dir();

        $options = new Options();
        $options->setChroot($templateDirectory);
        $options->setFontDir($tmpDirectory);
        $options->setFontCache($tmpDirectory);
        $options->setTempDir($tmpDirectory);

        $domPdf = new Dompdf(options: $options);

        return $domPdf;
    }

    public function addFont(DomPdf $domPdf, DomPdfFont $font): DomPdf
    {
        $domPdf->getFontMetrics()->registerFont(
            style: $font->getStyle(),
            remoteFile: $font->getRemoteFile(),
        );

        return $domPdf;
    }

    public function addHtml(DomPdf $domPdf, string $html): DomPdf
    {
        $domPdf->loadHtml(str: $html, encoding: 'UTF-8');
        $domPdf->setPaper(size: 'A4');
        $domPdf->render();

        return $domPdf;
    }

    public function addPageText(DomPdf $domPdf, DomPdfPageText $pageText): DomPdf
    {
        $domPdf->getCanvas()->page_text(
            x: $pageText->getX(),
            y: $pageText->getY(),
            text: $pageText->getText(),
            font: $pageText->getFont(),
            size: $pageText->getSize(),
            color: $pageText->getColor(),
            word_space: $pageText->getWordSpace(),
            char_space: $pageText->getCharSpace(),
            angle: $pageText->getAngle(),
        );

        return $domPdf;
    }

    public function print(DomPdf $domPdf): string
    {
        $output = $domPdf->output();
        if (\is_string($output)) {
            return $output;
        }

        // todo create exception
        throw new \Exception('For some reason we received no output.');
    }
}
