<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Constraints;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsPathExtension extends Constraint
{
    public string $message = 'The path "{{ path }}" must end with extension ".{{ extension }}".';
    public string $mode = 'strict';

    #[HasNamedArguments]
    public function __construct(
        public string $extension,
        array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
}
