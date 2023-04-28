<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Basics;

use Smoothie\ResumeExporter\Domain\Resume\Basics\Locations\Location;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews\Overview;
use Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles\Profiles;

class Basic
{
    public function __construct(
        private readonly string $email,
        private readonly string $label,
        private readonly string $name,
        private readonly string $phone,
        private readonly string $summary,
        private readonly string $url,
        private readonly Location $location,
        private readonly Profiles $profiles,
        private readonly Overview $overview,
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function location(): Location
    {
        return $this->location;
    }

    public function profiles(): array
    {
        return $this->profiles->profiles();
    }

    public function overview(): Overview
    {
        return $this->overview;
    }
}
