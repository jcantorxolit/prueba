<?php namespace Wgroup\Command;

use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Mail;
use Wgroup\Classes\ServiceCustomerTrackingAlert;
use Wgroup\NotifiedAlert\NotifiedAlert;

class TrackingAlertCommand extends ScheduledCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tracking:run';

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

            $modelNotify = new NotifiedAlert();
            $modelNotify->entityId = 999;
            $modelNotify->entityName = "logger";
            $modelNotify->isSendMail = 1;
            //$modelNotify->save();

            $service = new ServiceCustomerTrackingAlert();

            $beforeHours = $service->getAlertBeforeHours();

            $this->validate($beforeHours);

            $beforeDays = $service->getAlertBeforeDays();

            $this->validate($beforeDays);

            $beforeWeeks = $service->getAlertBeforeWeeks();

            $this->validate($beforeWeeks);

            $beforeMonths = $service->getAlertBeforeMonths();

            $this->validate($beforeMonths);



            //Action Plan Diagnostic
            $beforeHours = $service->getAlertActionPlanDiagnosticBeforeHours();

            $this->validateForDiagnostic($beforeHours);

            $beforeDays = $service->getAlertActionPlanDiagnosticBeforeDays();

            $this->validateForDiagnostic($beforeDays);

            $beforeWeeks = $service->getAlertActionPlanDiagnosticBeforeWeeks();

            $this->validateForDiagnostic($beforeWeeks);

            $beforeMonths = $service->getAlertActionPlanDiagnosticBeforeMonths();

            $this->validateForDiagnostic($beforeMonths);


            //Action Plan Management
            $beforeHours = $service->getAlertActionPlanManagementBeforeHours();

            $this->validateForManagement($beforeHours);

            $beforeDays = $service->getAlertActionPlanManagementBeforeDays();

            $this->validateForManagement($beforeDays);

            $beforeWeeks = $service->getAlertActionPlanManagementBeforeWeeks();

            $this->validateForManagement($beforeWeeks);

            $beforeMonths = $service->getAlertActionPlanManagementBeforeMonths();

            $this->validateForManagement($beforeMonths);


            //Action Plan Contract
            $beforeHours = $service->getAlertActionPlanContractBeforeHours();

            $this->validateForContract($beforeHours);

            $beforeDays = $service->getAlertActionPlanContractBeforeDays();

            $this->validateForContract($beforeDays);

            $beforeWeeks = $service->getAlertActionPlanContractBeforeWeeks();

            $this->validateForContract($beforeWeeks);

            $beforeMonths = $service->getAlertActionPlanContractBeforeMonths();

            $this->validateForContract($beforeMonths);

        } catch (\Exception $e) {
            Log::error($e);
            ////Log::info($e->getMessage());
            //Log::error($e->getTraceAsString());
        }
    }

    private function validate($models)
    {
        foreach ($models as $model)
        {
            //Envio de correo
            try {
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->eventDateTime;
                $params['Descripcion'] = $model->observation;

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger before ".$model->id;
                $modelNotify->isSendMail = 1;
                $modelNotify->save();

                //Mail::sendTo(['david.blandon@gmail.com' => 'Admin Person', $model->email => $model->fullName], 'rainlab.user::mail.alerta_seguimiento', $params);
                //Mail::sendTo('david.blandon@gmail.com', 'rainlab.user::mail.alerta_seguimiento', $params);
                //Mail::sendTo($model->email, 'rainlab.user::mail.alerta_seguimiento', $params);

                Mail::send('rainlab.user::mail.alerta_seguimiento', $params, function($message) use ($model){

                    $message->to($model->email, $model->fullName);
                    //$message->subject('This is a reminder');
                    $modelNotify = new NotifiedAlert();
                    $modelNotify->entityId = 999;
                    $modelNotify->entityName = "logger closure ".$model->id;
                    $modelNotify->isSendMail = 1;
                    $modelNotify->save();
                });

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "Tracking";
                $modelNotify->isSendMail = 1;
                $modelNotify->save();
            }
            catch (Exception $ex) {
                //Flash::error($ex->getMessage());
                Log::error($ex);
                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger error";
                $modelNotify->isSendMail = 1;
                $modelNotify->save();
            }
        }
    }

    private function validateForDiagnostic($models)
    {
        foreach ($models as $model)
        {
            //Envio de correo
            try {
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->closeDateTIme;
                $params['Descripcion'] = $model->description;
                $params['SGSST'] = $model->title;

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger before ".$model->id;
                $modelNotify->isSendMail = 1;
                $modelNotify->save();


                Mail::send('rainlab.user::mail.alerta_plan_accion_sgsst', $params, function($message) use ($model){

                    $message->to($model->email, $model->fullName);
                    //$message->subject('This is a reminder');
                    $modelNotify = new NotifiedAlert();
                    $modelNotify->entityId = 999;
                    $modelNotify->entityName = "logger closure ".$model->id;
                    $modelNotify->isSendMail = 1;
                    $modelNotify->save();
                });

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "action_plan_diagnostic";
                $modelNotify->isSendMail = 1;
                $modelNotify->save();
            }
            catch (Exception $ex) {
                //Flash::error($ex->getMessage());
                Log::error($ex);
                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger error";
                $modelNotify->isSendMail = 1;
                $modelNotify->save();
            }
        }
    }

    private function validateForManagement($models)
    {
        foreach ($models as $model)
        {
            //Envio de correo
            try {
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->closeDateTIme;
                $params['Descripcion'] = $model->description;
                $params['PE'] = $model->title;

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger before management ".$model->id;
                $modelNotify->isSendMail = 1;
                $modelNotify->save();


                Mail::send('rainlab.user::mail.alerta_plan_accion_pe', $params, function($message) use ($model){

                    $message->to($model->email, $model->fullName);
                    //$message->subject('This is a reminder');
                    $modelNotify = new NotifiedAlert();
                    $modelNotify->entityId = 999;
                    $modelNotify->entityName = "logger closure management ".$model->id;
                    $modelNotify->isSendMail = 1;
                    $modelNotify->save();
                });

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "action_plan_management";
                $modelNotify->isSendMail = 1;
                $modelNotify->save();
            }
            catch (Exception $ex) {
                //Flash::error($ex->getMessage());
                Log::error($ex);
                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger error management";
                $modelNotify->isSendMail = 1;
                //$modelNotify->message = $ex->getMessage();
                $modelNotify->save();
            }
        }
    }

    private function validateForContract($models)
    {
        foreach ($models as $model)
        {
            //Envio de correo
            try {
                $params['name'] = $model->fullName;
                $params['Empresa'] = $model->businessName;
                $params['Fecha'] = $model->closeDateTIme;
                $params['Descripcion'] = $model->description;
                $params['Contrato'] = $model->title;

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger before contract ".$model->id;
                $modelNotify->isSendMail = 1;
                $modelNotify->save();


                Mail::send('rainlab.user::mail.alerta_plan_accion_contratos', $params, function($message) use ($model){

                    $message->to($model->email, $model->fullName);
                    //$message->subject('This is a reminder');
                    $modelNotify = new NotifiedAlert();
                    $modelNotify->entityId = 999;
                    $modelNotify->entityName = "logger closure contract ".$model->id;
                    $modelNotify->isSendMail = 1;
                    $modelNotify->save();
                });

                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = $model->id;
                $modelNotify->entityName = "action_plan_contractor";
                $modelNotify->isSendMail = 1;
                $modelNotify->save();
            }
            catch (Exception $ex) {
                //Flash::error($ex->getMessage());
                Log::error($ex);
                $modelNotify = new NotifiedAlert();
                $modelNotify->entityId = 999;
                $modelNotify->entityName = "logger error contract";
                $modelNotify->isSendMail = 1;
                //$modelNotify->message = $ex->getMessage();
                $modelNotify->save();
            }
        }
    }
}
