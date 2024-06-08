<?php

namespace AdeN\Api\Console;

use AdeN\Api\Helpers\CmsHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Storage;


class PurgeExportedZipFilesCommand extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'aden:purgefiles';

    /**
     * @var string The console command description.
     */
    protected $description = 'Delete exported zip files older that 10 days.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $path = CmsHelper::getStorageDirectory('zip/exports/');
        $this->output->writeln('Start file purge at ' .  $path);

        if ($handle = opendir($path)) {
            $now = Carbon::now();
            while (false !== ($file = readdir($handle))) {
                $filelastmodified = filemtime($path . $file);
                $time = Carbon::createFromTimestamp($filelastmodified);
                $extension = pathinfo($path . $file, PATHINFO_EXTENSION);
                if (strtolower($extension) == "zip" && $time->diffInDays($now) > 10) {
                    unlink($path . $file);
                    $this->output->writeln($path . $file);
                }
            }

            closedir($handle);
        }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
