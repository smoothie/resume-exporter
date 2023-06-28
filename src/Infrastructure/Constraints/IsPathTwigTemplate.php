<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
class IsPathTwigTemplate extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new IsPathExtension(extension: 'twig'),
            new Assert\NotNull(),
            new Assert\NotBlank(),
        ];
    }
}
