<?php namespace Wgroup\Command;

use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Mail;
use Log;
use Wgroup\Classes\ServiceCustomerTrackingAlert;
use Wgroup\CustomerImprovementPlan\CustomerImprovementPlanService;
use Wgroup\NotifiedAlert\NotifiedAlert;
use Wgroup\NotifiedAlert\NotifiedAlertImprovementPlan;

class ImprovementPlanAlertCommand extends ScheduledCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'improvement:run';

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
        //$scheduler->everyHours(1)
        return $scheduler->everyMinutes(5);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {

            $service = new CustomerImprovementPlanService();

            $beforeHours = $service->getAlertBeforeHours();

            $this->validate($beforeHours);

            $beforeDays = $service->getAlertBeforeDays();

            $this->validate($beforeDays);

            $beforeWeeks = $service->getAlertBeforeWeeks();

            $this->validate($beforeWeeks);

            $beforeMonths = $service->getAlertBeforeMonths();

            $this->validate($beforeMonths);




            $beforeHours = $service->getTrackingAlertBeforeHours();

            $this->validateForTracking($beforeHours);

            $beforeDays = $service->getTrackingAlertBeforeDays();

            $this->validateForTracking($beforeDays);

            $beforeWeeks = $service->getTrackingAlertBeforeWeeks();

            $this->validateForTracking($beforeWeeks);

            $beforeMonths = $service->getTrackingAlertBeforeMonths();

            $this->validateForTracking($beforeMonths);




            $beforeHours = $service->getActionPlanAlertBeforeHours();

            $this->validateForActionPlan($beforeHours);

            $beforeDays = $service->getActionPlanAlertBeforeDays();

            $this->validateForActionPlan($beforeDays);

            $beforeWeeks = $service->getActionPlanAlertBeforeWeeks();

            $this->validateForActionPlan($beforeWeeks);

            $beforeMonths = $service->getActionPlanAlertBeforeMonths();

            $this->validateForActionPlan($beforeMonths);




            $beforeHours = $service->getActionPlanNotificationAlertBeforeHours();

            $this->validateForActionPlanNotification($beforeHours);

            $beforeDays = $service->getActionPlanNotificationAlertBeforeDays();

            $this->validateForActionPlanNotification($beforeDays);

            $beforeWeeks = $service->getActionPlanNotificationAlertBeforeWeeks();

            $this->validateForActionPlanNotification($beforeWeeks);

            $beforeMonths = $service->getActionPlanNotificationAlertBeforeMonths();

            $this->validateForActionPlanNotification($beforeMonths);





            $beforeHours = $service->getActionPlanTaskAlertBeforeHours();

            $this->validateForActionPlanTask($beforeHours);

            $beforeDays = $service->getActionPlanTaskAlertBeforeDays();

            $this->validateForActionPlanTask($beforeDays);

            $beforeWeeks = $service->getActionPlanTaskAlertBeforeWeeks();

            $this->validateForActionPlanTask($beforeWeeks);

            $beforeMonths = $service->getActionPlanTaskAlertBeforeMonths();

            $this->validateForActionPlanTask($beforeMonths);

        } catch (\Exception $e) {
            Log::error($e);
            ////Log::info($e->getMessage());
            //Log::error($e->getTraceAsString());
        }
    }

    private function validate($models)
    {
        foreach ($models as $model) {
            //Envio de correo
            try {
                $params['module'] = $model->module;
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->endDate;
                $params['Descripcion'] = $model->description;

                Mail::send('rainlab.user::mail.plan_mejoramiento_asesores', $params, function ($message) use ($model) {
                    //$message->from('noreply@sylogi.com.co', 'Sylogi Software');
                    $message->to($model->email, $model->fullName);
                });

                $modelNotify = new NotifiedAlertImprovementPlan();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "improvement_plan";
                $modelNotify->isSendMail = 1;
                $modelNotify->responsible = $model->responsible;
                $modelNotify->responsibleType = $model->responsibleType;
                $modelNotify->save();
            } catch (\Exception $ex) {
                Log::error($ex);
            }
        }
    }

    private function validateForTracking($models)
    {
        foreach ($models as $model) {
            //Envio de correo
            try {
                $params['module'] = $model->module;
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->startDate;
                $params['Descripcion'] = $model->observation;


                // Mail::send('rainlab.user::mail.plan_mejoramiento_seguimiento_asesores', $params, function ($message) use ($model) {

                //     $message->to($model->email, $model->fullName);

                // });

                $modelNotify = new NotifiedAlertImprovementPlan();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "improvement_plan_tracking";
                $modelNotify->isSendMail = 1;
                $modelNotify->responsible = $model->responsible;
                $modelNotify->responsibleType = $model->responsibleType;
                $modelNotify->save();
            } catch (\Exception $ex) {
                Log::error($ex);
            }
        }
    }

    private function validateForActionPlan($models)
    {
        foreach ($models as $model) {
            //Envio de correo
            try {
                $params['module'] = $model->module;
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->endDate;
                $params['Descripcion'] = $model->description;
                $params['Actividad'] = $model->activity;
                $params['Responsable'] = $model->responsible;

                Mail::send('rainlab.user::mail.plan_accion_asesores', $params, function ($message) use ($model) {

                    $message->to($model->email, $model->fullName);
                    //$message->subject('This is a reminder');

                });

                $modelNotify = new NotifiedAlertImprovementPlan();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "improvement_plan_action_plan";
                $modelNotify->isSendMail = 1;
                $modelNotify->responsible = $model->responsible;
                $modelNotify->responsibleType = $model->responsibleType;
                $modelNotify->save();
            } catch (\Exception $ex) {
                Log::error($ex);
            }
        }
    }

    private function validateForActionPlanNotification($models)
    {
        foreach ($models as $model) {
            //Envio de correo
            try {
                $params['module'] = $model->module;
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->endDate;
                $params['Descripcion'] = $model->description;
                $params['Actividad'] = $model->activity;
                $params['Responsable'] = $model->responsible;

                Mail::send('rainlab.user::mail.notificacion_plan_accion_asesores', $params, function ($message) use ($model) {

                    $message->to($model->email, $model->fullName);

                });

                $modelNotify = new NotifiedAlertImprovementPlan();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "improvement_plan_action_plan_notification";
                $modelNotify->isSendMail = 1;
                $modelNotify->responsible = $model->responsible;
                $modelNotify->responsibleType = $model->responsibleType;
                $modelNotify->save();
            } catch (\Exception $ex) {
                Log::error($ex);
            }
        }
    }

    private function validateForActionPlanTask($models)
    {
        foreach ($models as $model) {
            //Envio de correo
            try {
                $params['module'] = $model->module;
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->startDate;
                $params['Descripcion'] = $model->descriptionPlan;
                $params['Actividad'] = $model->activity;
                $params['Descripcion_Tarea'] = $model->description;

                Mail::send('rainlab.user::mail.tarea_plan_accion_asesores', $params, function ($message) use ($model) {

                    $message->to($model->email, $model->fullName);
                });

                $modelNotify = new NotifiedAlertImprovementPlan();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "improvement_plan_action_plan_task";
                $modelNotify->isSendMail = 1;
                $modelNotify->responsible = $model->responsible;
                $modelNotify->responsibleType = $model->responsibleType;
                $modelNotify->save();
            } catch (\Exception $ex) {
                Log::error($ex);
            }
        }
    }
}
