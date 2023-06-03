<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Mapping\InputMapping;
use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Domain\Resume\ResumeFactory;
use Smoothie\ResumeExporter\Domain\Resume\ResumeRepository;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class JsonResumeRepository implements ResumeRepository, InputMapping
{
    public function __construct(
        private readonly MappingStrategy $mappingStrategy,
        private readonly ResumeFactory $resumeFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    public function translateToCanonical(Input $input, array $canonicalData): Resume
    {
        $this->propertyAccessor->setValue(
            objectOrArray: $canonicalData,
            propertyPath: '[Meta][Internal][ResumeId]',
            value: $input->getInputId(),
        );

        $this->resumeFactory->validate($canonicalData);

        return $this->resumeFactory->fromArray(canonicalData: $canonicalData);
    }

    public function firstAndTranslate(Input $input): Resume
    {
        $inputMap = $input->getMap();
        $from = $input->getInput();

        $this->mappingStrategy->validate(map: $inputMap, from: $from, settings: []);
        $map = $this->mappingStrategy->normalize(map: $inputMap, from: $from, settings: []);
        $this->mappingStrategy->validate(map: $map, from: $from, settings: []);
        $canonical = $this->mappingStrategy->translate(map: $map, from: $from, settings: []);

        return $this->translateToCanonical(input: $input, canonicalData: $canonical);
    }
}
