<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Symfony\Component\Process\Process;

class JsonNataStrategy implements MappingStrategy
{
    public function __construct(private string $pythonCli, private string $pythonJsonataCli)
    {
    }

    public function translate(array $map, array $from, array $settings): array
    {
        $inputSource = '--input='.$settings['input'];
        $expression = '--expression='.$settings['map'];

        $process = new Process(command: [
            $this->pythonCli,
            $this->pythonJsonataCli,
            $inputSource,
            $expression,
            '--icharset=utf-8',
            '--ocharset=utf-8',
        ], env: [
            'PYTHONPATH' => \dirname($this->pythonJsonataCli, 3),
            'APP_ENV' => false,
            'SYMFONY_DOTENV_VARS' => false,
        ]);

        $process->run();

        $status = $process->getStatus();
        $error = $process->getErrorOutput();

        return json_decode($process->getOutput(), associative: true, flags: \JSON_THROW_ON_ERROR);
    }

    public function normalize(array $map, array $from, array $settings): array
    {
        return $from;
    }

    public function validate(array $map, array $from, array $settings): void
    {
    }
}
