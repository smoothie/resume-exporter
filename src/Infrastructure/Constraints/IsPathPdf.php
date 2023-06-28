<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
class IsPathPdf extends Compound
{
    /**
     * @return (Assert\NotBlank|Assert\NotNull|IsPathExtension)[]
     *
     * @psalm-return list{IsPathExtension, Assert\NotNull, Assert\NotBlank}
     */
    protected function getConstraints(array $options): array
    {
        return [
            new IsPathExtension(extension: 'pdf'),
            new Assert\NotNull(),
            new Assert\NotBlank(),
        ];
    }
}
