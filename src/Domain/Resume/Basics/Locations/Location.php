<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Basics\Locations;

class Location
{
    public function __construct(
        private readonly string $address,
        private readonly string $city,
        private readonly string $countryCode,
        private readonly string $postalCode,
    ) {
    }

    public function address(): string
    {
        return $this->address;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }
}
