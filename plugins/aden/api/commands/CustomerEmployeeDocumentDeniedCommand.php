<?php

namespace AdeN\Api\Commands;

use AdeN\Api\Modules\Customer\Employee\Document\CustomerEmployeeDocumentRepository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\CustomerEvaluationMinimumStandardTracking0312Repository;
use Carbon\Carbon;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Mail;
use Log;
use DB;


class CustomerEmployeeDocumentDeniedCommand extends ScheduledCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'employee-document-denied:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Documents denied for inactive employees in active clients.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * When a command should run
     *
     * @param Scheduler $scheduler
     * @return \Indatus\Dispatcher\Scheduling\Schedulable
     */
    public function schedule(Schedulable $scheduler)
    {
        Log::info("schedule CustomerEmployeeDocumentDeniedCommand");
        
        //$this->fire();

        return $scheduler
            ->daily()
            ->hours(4);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {             
            CustomerEmployeeDocumentRepository::executeDenied();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
