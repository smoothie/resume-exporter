<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Dompdf\Dompdf;
use Dompdf\Options;

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

    public function addFont(Dompdf $domPdf, DomPdfFont $font): Dompdf
    {
        $domPdf->getFontMetrics()->registerFont(
            style: $font->getStyle(),
            remoteFile: $font->getRemoteFile(),
        );

        return $domPdf;
    }

    public function addHtml(Dompdf $domPdf, string $html): Dompdf
    {
        $domPdf->loadHtml(str: $html, encoding: 'UTF-8');
        $domPdf->setPaper(size: 'A4');
        $domPdf->render();

        return $domPdf;
    }

    public function addPageText(Dompdf $domPdf, DomPdfPageText $pageText): Dompdf
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

    public function print(Dompdf $domPdf): string
    {
        $output = $domPdf->output();
        if (\is_string($output)) {
            return $output;
        }

        throw new \Exception('For some reason we received no output.');
    }
}
