<?php

namespace YonisSavary\MakemkvAutoRipper;

class AutoRipperFactory
{
    public static function fromArgs(array $argv): AutoRipper
    {
        $values = getopt(
            "d::o::n::",
            ["device::", "out::", "name::"]
        );

        $device = $values["d"] ?? $values["device"] ?? null;
        $outputDirectory = $values["o"] ?? $values["out"] ?? null;
        $identifier = $values["n"] ?? $values["name"] ?? null;

        return new AutoRipper($device, $outputDirectory, $identifier);
    }
}