<?php

declare(strict_types=1);

namespace App\Test\Model;

class Input extends \ArrayObject
{
    public function withSource(string $source): self
    {
        $sources = $this['sources'] ?? [];
        $sources[$source] = 'on';

        $this['sources'] = $source;

        return $this;
    }

    public function withMode(string $mode): self
    {
        $this['mode'] = $mode;

        return $this;
    }

    public function withBodyNum(string $bodyNum): self
    {
        $this['bodynum'] = $bodyNum;

        return $this;
    }

    public function withDriverDate(\DateTimeInterface $driverDate): self
    {
        $this['driver_date'] = $driverDate->format('d.m.Y');

        return $this;
    }

    public function withDriverNumber(string $driverNumber): self
    {
        $this['driver_number'] = $driverNumber;

        return $this;
    }

    public function withOsago(string $osago): self
    {
        $this['osago'] = $osago;

        return $this;
    }

    public function withPts(string $pts): self
    {
        $this['pts'] = $pts;

        return $this;
    }

    public function withRegNum(string $regNum): self
    {
        $this['regnum'] = $regNum;

        return $this;
    }

    public function withReqDate(\DateTimeInterface $reqDate): self
    {
        $this['reqdate'] = $reqDate->format('d.m.Y');

        return $this;
    }

    public function withSts(string $sts): self
    {
        $this['ctc'] = $sts;

        return $this;
    }

    public function withVin(string $vin): self
    {
        $this['vin'] = $vin;

        return $this;
    }
}
