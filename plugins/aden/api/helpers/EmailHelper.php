<?php

namespace AdeN\Api\Helpers;

use AdeN\Api\Modules\User\Message\UserMessageRepository;
use Carbon\Carbon;
use Mail;
use Log;

/**
 * Parse and build criteria expressions
 */
class EmailHelper {

    public static function notifyScheduledShift($data, $templateName = null)
    {
        $templateName = $templateName ? $templateName : "rainlab.user::mail.notificacion_covid_programacion_turno";

        $data = is_array($data) ? $data : [ $data ];

        foreach ($data as $body) {
            $params['startDate'] = $body->startDate ? Carbon::parse($body->startDate)->format('d/m/Y') : null;;
            $params['endDate'] = $body->endDate ? Carbon::parse($body->endDate)->format('d/m/Y') : null;;
            $params['startTime'] = $body->startTime ? Carbon::parse($body->startTime)->format('h:i a') : null;
            $params['endTime'] = $body->endTime ? Carbon::parse($body->endTime)->format('h:i a') : null;            
            $params['subject'] = $body->subject;

            $sender = new \stdClass();
            $sender->body = $params;
            $sender->template = $templateName;
            $sender->email = $body->email;
            $sender->name = $body->name;
            EmailHelper::sendMail($sender);
        }        
    }

    private static function sendMail($sender)
    {
        try {                    
            if ($sender && $sender->email) {
                Mail::send($sender->template, $sender->body, function ($message) use ($sender) {                        
                    $message->to($sender->email, $sender->name);
                });

                if (!empty($sender->shouldInsertNotification) && $sender->shouldInsertNotification)
                {
                    $repository = new UserMessageRepository();

                    $entity = new \stdClass();

                    $module = $sender->body["Modulo"];
                    $url = $sender->body["url"];

                    $entity->id = 0;
                    $entity->userId = $sender->userId;
                    $entity->from = "Centro de notificaciones";
                    $entity->subject = "Documentos exportados: {$module}";
                    $entity->content = "Descargar el archivo <a target='_blank' href='{$url}'>Aquí</a>";
                    $entity->isReaded = false;
                    $entity->readedAt = null;
            
                    $repository->insertOrUpdate($entity);
                }

                if (!empty($sender->shouldInsertConsolidate) && $sender->shouldInsertConsolidate)
                {
                    $repository = new UserMessageRepository();

                    $entity = new \stdClass();

                    $customer = $sender->body["customer"];

                    $entity->id = 0;
                    $entity->userId = $sender->userId;
                    $entity->from = "Centro de notificaciones";
                    $entity->subject = "Consolidación finalizada";
                    $entity->content = "La consolidación de los análisis de indicadores de la empresa {$customer} finalizó.";
                    $entity->isReaded = false;
                    $entity->readedAt = null;
            
                    $repository->insertOrUpdate($entity);
                }
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public static function notifyExportAttachment($data, $templateName = null)
    {
        $templateName = $templateName ? $templateName : 'rainlab.user::mail.notificacion_exportacion_documento_completado';

        $params['Modulo'] = $data->module;
        $params['url'] = $data->url;

        $sender = new \stdClass();
        $sender->body = $params;
        $sender->template = $templateName;
        $sender->email = $data->email;
        $sender->name = $data->name;
        $sender->userId = $data->userId;
        $sender->shouldInsertNotification = true;
        EmailHelper::sendMail($sender);        
    }

    public static function notifyConsolidationCompleted($data, $templateName = null)
    {
        $templateName = $templateName ? $templateName : 'rainlab.user::mail.notification_consolidate_complete';

        $params['Modulo'] = $data->module;
        $params['customer'] = $data->customer;

        $sender = new \stdClass();
        $sender->body = $params;
        $sender->template = $templateName;
        $sender->email = $data->email;
        $sender->name = $data->name;
        $sender->userId = $data->userId;
        $sender->module = "";
        $sender->url = "";
        $sender->shouldInsertConsolidate = true;
        EmailHelper::sendMail($sender);        
    }
}