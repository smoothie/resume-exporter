<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Resume;

use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Domain\Resume\ResumeRepository;

class ReadResumeRepository implements ResumeRepository
{
    public function firstAndTranslate(Input $input): Resume
    {
        return EmptyResume::create();
    }
}
