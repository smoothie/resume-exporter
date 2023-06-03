<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigFactory
{
    public function createFromDirectory(string $directory): Environment
    {
        if (is_readable($directory) === false) {
            // todo
            throw new DirectoryNotFoundException(
                sprintf('TwigFactory: The "%s" directory does not exist.', $directory),
            );
        }

        $loader = new FilesystemLoader($directory);
        $twig = new Environment($loader);

        return $twig;
    }
}
