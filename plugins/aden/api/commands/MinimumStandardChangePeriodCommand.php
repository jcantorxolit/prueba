<?php

namespace AdeN\Api\Commands;

use AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\CustomerEvaluationMinimumStandard0312Repository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\CustomerEvaluationMinimumStandardTracking0312Repository;
use Carbon\Carbon;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Mail;
use Log;
use DB;


class MinimumStandardChangePeriodCommand extends ScheduledCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'em-change-period:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change period minimum standard resolution 0312.';

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
        Log::info("schedule MinimumStandardChangePeriodCommand");

        $contrabSchedule = $this->getParameter('crontab');

        if ($contrabSchedule) {
            $commands = explode(' ', $contrabSchedule->item);            
            if (count($commands) == 5) {
                Log::info("schedule run ". $contrabSchedule->item);
				//if ($contrabSchedule->item == '*/15 * * * *') {
				//	$this->fire();	
				//}                
                return $scheduler->setSchedule(
                    $this->parseCommand($commands[0]),
                    $this->parseCommand($commands[1]),
                    $this->parseCommand($commands[2]),
                    $this->parseCommand($commands[3]),
                    $this->parseCommand($commands[4])
                );                
            }
        }

        Log::info("schedule run monthly");

        return $scheduler->monthly();
    }

    private function parseCommand($command)
    {
        if (is_numeric($command)) {
            return intval($command);
        }
        
        if (starts_with($command, '[') && ends_with($command, ']')) {            
            return array_map(function($item) {
                return is_numeric($item) ? intval($item) : 0;
            }, explode(',', $command));
        }

        return "$command";
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {             
 
            CustomerEvaluationMinimumStandard0312Repository::executeChangePeriod();
            $this->sendMail();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    private function sendMail()
    {     
        $recipent = $this->getParameter('email_notifica');

        if ($recipent) {
            try {                
                $params = [
                    "previous" => Carbon::now()->addYears(-1)->year,
                    "current" => Carbon::now()->year,
                ];
                Mail::send('rainlab.user::mail.notification_cambio_periodo_em0312', $params, function ($message) use ($recipent) {                    
                    $message->to($recipent->item);
                });
            } catch (\Exception $ex) {
                Log::error($ex);
            }
        }
    }

    private function getParameter($value)
    {
        return DB::table('system_parameters')
        ->where('group', 'em_0312_period_change')
        ->where('value', $value)
        ->first();
    }
}
