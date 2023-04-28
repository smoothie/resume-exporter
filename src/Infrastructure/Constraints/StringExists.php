<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Constraints;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
class StringExists extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Length(min: 0),
            new Assert\Type(type: 'string'),
        ];
    }
}
