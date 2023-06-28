<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

#[\Attribute]
class IsPathExtensionValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof IsPathExtension) {
            throw new UnexpectedTypeException($constraint, IsPathExtension::class);
        }

        // should ignore null and empty values to allow  other constraints (NotBlank, NotNull, etc.)
        if ($value === null || $value === '') {
            return;
        }

        if (! \is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (mb_strtolower(pathinfo($value, \PATHINFO_EXTENSION)) !== $constraint->extension) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ path }}', $value)
                ->setParameter('{{ extension }}', $constraint->extension)
                ->addViolation();
        }
    }
}
