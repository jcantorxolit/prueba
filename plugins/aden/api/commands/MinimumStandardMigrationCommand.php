<?php

namespace AdeN\Api\Commands;

use AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\CustomerEvaluationMinimumStandardTracking0312Repository;
use Carbon\Carbon;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Mail;
use Log;
use DB;


class MinimumStandardMigrationCommand extends ScheduledCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'em-migration:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migration minimum standard resolution 0312.';

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
        Log::info("schedule MinimumStandardMigrationCommand");

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

            $previousPeriod = $this->getParameter('periodo_previo');
            
            $today = Carbon::now('America/Bogota');

            $previousTime = Carbon::now()->addMonths(-1);
            $lastTime = Carbon::now()->addMonths(-1);            

            if ($previousPeriod) {
                $periods = -1 * $previousPeriod->item;
                $previousTime = Carbon::now()->addMonths($periods);
                $lastTime = Carbon::now()->addMonths($periods);
            }
            
            $diffInMonths = $today->diffInMonths($previousTime);            

            $criteria = $this->createCriteria();

            for ($i = 1; $i <= $diffInMonths; $i++) {
                $currentTime = $lastTime->addMonths(1);
                $criteria->fromYear = $previousTime->year;
                $criteria->fromMonth = $previousTime->month;
                $criteria->toYear = $currentTime->year;
                $criteria->toMonth = $currentTime->month;    
                
                if ($criteria->fromMonth == 12 && $criteria->toMonth == 1 && $criteria->fromYear < $criteria->toYear) {
                    continue;
                }

                Log::info('from::' . $criteria->fromYear .'/'. $criteria->fromMonth . ' to::'. $criteria->toYear .'/'. $criteria->toMonth);
                
                CustomerEvaluationMinimumStandardTracking0312Repository::migratePreviousMonthlyReport($criteria);
                $previousTime = $previousTime->addMonth();
                $this->sendMail($criteria);
            }

        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    private function createCriteria()
    {
        $criteria = new \stdClass();                
        $criteria->createdBy = 1;
        $criteria->updatedBy = 1;
        $criteria->currentYear = Carbon::now()->year;
        $criteria->currentMonth = Carbon::now()->month;

        return $criteria;
    }

    private function getParameter($value)
    {
        return DB::table('system_parameters')
        ->where('group', 'em_0312_migration')
        ->where('value', $value)
        ->first();
    }

    private function sendMail($criteria)
    {
        $params['previous'] = "{$criteria->fromYear}{$criteria->fromMonth}";
        $params['current'] = "{$criteria->toYear}{$criteria->toMonth}";
      
        $recipent = $this->getParameter('email_notifica');

        if ($recipent) {
            try {
                Mail::send('rainlab.user::mail.notification_migracion_em0312', $params, function ($message) use ($recipent) {                    
                    $message->to($recipent->item);
                });
            } catch (\Exception $ex) {
                Log::error($ex);
            }
        }
    }
}
