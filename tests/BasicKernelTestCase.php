<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Pdfbox\Driver\Pdfbox;
use Pdfbox\Processor\PdfFile;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BasicKernelTestCase extends KernelTestCase
{
    use MockeryPHPUnitIntegration;

    public function getTemplateDoublesDirectory(string $path = ''): string
    {
        return __DIR__.'/Doubles/Files/Templates/'.$path;
    }

    public function getTmpDirectory(string $path = ''): string
    {
        return sys_get_temp_dir().'/'.$path;
    }

    public function buildPdfBox(): PdfFile
    {
        $pdfboxPath = \dirname(__FILE__, 2).'/infrastructure/pdfbox.jar';
        if (is_readable($pdfboxPath) === false) {
            throw new \Exception(sprintf('Unable to find PdfFile (path: %1$s)', $pdfboxPath));
        }

        return new PdfFile(
            new Pdfbox(
                '/usr/bin/java',
                $pdfboxPath,
                new NullLogger(),
            ),
        );
    }
}
