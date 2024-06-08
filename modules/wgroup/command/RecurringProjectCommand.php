<?php namespace Wgroup\Command;

use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Mail;
use Wgroup\Classes\ServiceCustomerProject;
use Wgroup\Classes\ServiceCustomerTrackingAlert;
use Wgroup\CustomerInternalProject\CustomerInternalProjectService;
use Wgroup\NotifiedAlert\NotifiedAlert;

class RecurringProjectCommand extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'recurring:run';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

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
		//TODO Production
		return $scheduler
			->daily()
			->hours(5);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try {

			$service = new ServiceCustomerProject();
			$serviceInternal = new CustomerInternalProjectService();

			$service->saveRecurringProject();
			$service->saveRecurringProjectAgent();
			$serviceInternal->saveRecurringProject();
			$serviceInternal->saveRecurringProjectAgent();

		} catch (\Exception $e) {
		}
	}
}
