<?php

namespace YonisSavary\MakemkvAutoRipper;

use Symfony\Component\Process\Process;

class AutoRipper
{
    protected string $device;
    protected string $outDirectory;
    protected string $identifier;

    protected ?Process $process = null;

    const COLOR_BLACK   = 40;
    const COLOR_RED     = 41;
    const COLOR_GREEN   = 42;
    const COLOR_YELLOW  = 43;
    const COLOR_BLUE    = 44;
    const COLOR_MAGENTA = 45;
    const COLOR_CYAN    = 46;
    const COLOR_WHITE   = 47;
    const COLOR_DEFAULT = 49;
    const COLOR_RESET   = 0;

    public function __construct(
        string $device = "dev/sr0",
        string $outDirectory = null,
        ?string $identifier = null
    ) {
        echo "\n\033[s";

        $this->identifier = $identifier ?? "Autoripper";
        $this->device = $device;

        $outDirectory ??= (getcwd() . "/out");
        $this->outDirectory = $outDirectory;

        if (!file_exists($device))
            die("Device [$device] not found \n");

        $this->log("Using device $device");

        if (!is_dir($outDirectory))
        {
            $this->log("Making directory $outDirectory");
            mkdir($outDirectory, recursive: true);
        }
        $this->log("Using output directory $outDirectory");

        declare(ticks = 1);
        pcntl_signal(SIGINT, fn() => $this->shutdown());
    }

    public function log(string ...$lines)
    {
        $this->cleanup();

        foreach ($lines as $line)
            echo $line . "\n";

        echo "\033[s";
    }


    public function shutdown()
    {
        $this->cleanup();
        if ($this->process)
        {
            $this->log("Shutting down process");
            $this->process->stop();
        }
        exit;
    }

    public function getDVDOutDirectory(string $dvd): string
    {
        return preg_replace("/\\/{2,}/", "/", $this->outDirectory . "/" . $dvd);
    }

    public function cleanup()
    {
        echo "\033[u\033[0J";
    }

    public function banner(string $string, int $color, bool $atSavedPosition=true)
    {
        $bannerSize = strlen($string) + (3*2);

        $color = "\033[1;{$color}m";
        $reset = "\033[0m";

        if ($atSavedPosition)
        {
            $this->cleanup();
            echo "\033[u\n";
        }

        echo "   " . $color . str_repeat(" ", $bannerSize)                    . $reset . "\n";
        echo "   " . $color . str_pad($string, $bannerSize, " ", STR_PAD_BOTH). $reset . "\n";
        echo "   " . $color . str_repeat(" ", $bannerSize)                    . $reset . "\n";
    }

    public function waitForDVD()
    {
        $device = $this->device;

        while (true)
        {
            $dvdName = trim(shell_exec("blkid -o value -s LABEL \"$device\""));

            if ($dvdName)
            {
                $outPath = $this->getDVDOutDirectory($dvdName);

                if (!is_dir($outPath))
                {
                    mkdir($outPath);
                    return $dvdName;
                }

                $this->banner(basename($outPath) . " already exists.", self::COLOR_BLUE);
                clearstatcache();
            }
            else
            {
                $this->banner("Waiting for DVD", self::COLOR_BLUE);
            }

            sleep(1);
        }

    }

    public function ripDVD(string $dvdName)
    {
        $device = $this->device;
        $outPath = $this->getDVDOutDirectory($dvdName);

        $this->banner("Retrieving DVD infos...", self::COLOR_BLUE);

        $discNumber = trim(shell_exec("makemkvcon -r --cache=1 info disc:9999 | grep \"$device\" | grep -Eoh \"[0-9]+\" | head -n 1"));

        if (!strlen($discNumber))
            die("Cannot detect disc number \n");

            $logFile = getcwd() . "/logs/" . date("Y-m-d H-i-s") . ".txt";

        $this->log("Ripping disc:$discNumber");
        $this->log("$dvdName in $outPath");
        $this->log("with logs in $logFile");

        $this->process = $proc = new Process([
            "makemkvcon",
            "--messages", $logFile,
            "--progress", "-stdout",
            "--cache", "512MB",
            "--minlength", "600",
            "mkv",
            "disc:$discNumber",
            "all",
            $outPath,
        ]);

        $this->banner("Starting rip process...", self::COLOR_BLUE);

        $proc->start();

        $status = "Ripping $dvdName";
        $progress = "";

        while ($proc->isRunning())
        {
            if ($error = $proc->getIncrementalErrorOutput())
            {
                $this->log(
                    "\033[" .(self::COLOR_RED-10) . ";1m",
                    "Got error ! $error",
                    "\033[0m",
                );
            }

            foreach (explode("\n", $proc->getIncrementalOutput()) as $line)
            {
                $line = trim($line);
                if (!$line)
                    continue;

                if (str_starts_with($line, "Current progress"))
                    $progress = preg_replace("/.+, ?/", "", $line);
                else
                    $status = preg_replace("/.+: ?/", "", $line);
            }

            $this->banner(
                trim($status . ($progress ? " : $progress": "")),
                self::COLOR_MAGENTA
            );

            sleep(1);
        }

        $this->log("End of rip !");
    }

    public function launch()
    {
        set_time_limit(0);

        $identifier = $this->identifier;
        $device = $this->device;

        $this->log("");
        $this->banner("[$identifier] Autorip from [$device]", self::COLOR_GREEN, false);
        echo "\n\033[s";

        while (true)
        {
            $dvdName = $this->waitForDVD();
            $this->ripDVD($dvdName);
        }
    }
}