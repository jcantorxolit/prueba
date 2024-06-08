<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Quote;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Models\CustomerDto;
use Wgroup\QuoteDetail\QuoteDetail;
use Wgroup\QuoteDetail\QuoteDetailDTO;
use Mail;

/**
 * Description of QuoteDTO
 *
 * @author jdblandon
 */
class QuoteDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoSummary($model);
                break;
            case "3":
                $this->getBasicInfoResponislbe($model);
                break;
            default:
                $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo Quote
     */
    private function getBasicInfo($model) {

        $this->id = $model->id;
        $this->customer = CustomerDto::parse($model->customer, "4");
        $this->deadline = $model->deadline;
        $this->expenses = $model->expenses;
        $this->tax = $model->tax;
        $this->total = $model->total;
        $this->totalModified = $model->totalModified;
        $this->status = $model->getStatus();
        $this->agent = $model->agent;
        $this->observation = $model->observation;
        $this->details = QuoteDetailDTO::parse($model->details);
        //$this->created_at = $model->created_at->format('d/m/Y');
        //$this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoSummary($model)
    {
        $this->id = $model->id;
        $this->customer = $model->customer;
        $this->deadline = Carbon::parse($model->deadline);
        $this->deadlineFormat = Carbon::parse($model->deadline)->format('d/m/Y');
        $this->total = $model->total;
        $this->totalModified = $model->totalModified;
        $this->status = $model->getStatus();
    }

    private function getBasicInfoResponislbe($model)
    {
        $this->id = $model->id;
        $this->fullName = $model->name ." ". $model->firstName ." ". $model->lastName;
        $this->email = $model->email;
        $this->role = $model->role;
        $this->isActive = false;
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Quote::find($object->id))) {
                // No existe
                $model = new Quote();
                $isEdit = false;
            }
        } else {
            $model = new Quote();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customer->id == "-S-" ? null : $object->customer->id;
        $model->deadline = $object->deadline; //Carbon::createFromFormat('d/m/Y H:i:s', $object->deadline);
        $model->expenses = $object->expenses;
        $model->tax = $object->tax;
        $model->total = $object->total;
        $model->totalModified = $object->totalModified;
        $model->agent_id = 1;
        $model->status = $object->status->value == "-S-" ? null : $object->status->value;
        $model->observation = $object->observation;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

        }

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto
        if ($object->details) {
            foreach ($object->details as $detail) {
                $isDetailEdit = true;
                if ($detail) {
                    if ($detail->id) {
                        // Existe
                        if (!($quoteDetail = QuoteDetail::find($detail->id))) {
                            $isDetailEdit = false;
                            $quoteDetail = new QuoteDetail();
                        }
                    } else {
                        $quoteDetail = new QuoteDetail();
                        $isDetailEdit = false;
                    }

                    $quoteDetail->quote_id    = $model->id;
                    $quoteDetail->service_id = $detail->service->id;
                    $quoteDetail->quantity = $detail->quantity;
                    $quoteDetail->hour = $detail->hour;
                    $quoteDetail->total = $detail->total;
                    $quoteDetail->totalModified = $detail->totalModified;

                    if ($isDetailEdit) {

                        // actualizado por
                        $quoteDetail->updatedBy = $userAdmn->id;

                        // Guarda
                        $quoteDetail->save();

                        // Actualiza timestamp
                        $quoteDetail->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $quoteDetail->createdBy = $userAdmn->id;
                        $quoteDetail->updatedBy = $userAdmn->id;

                        // Guarda
                        $quoteDetail->save();


                    }
                }
            }
        }


        //Envio email

        try {
            //Log::info("Envio correo proyecto");

            $html = "";

            if ($object->details) {

                $isAlterRow = false;

                $keys = array("Servicio", "UN", "Cantidad", "Valor total", "Link");

                $thead = "";
                $tbody = "";
                $cols = "";
                $rows = "";

                foreach ($keys as $key) {
                    $cols .= '<th class="sorting" tabindex="0" aria-controls="dtReportDyn" rowspan="1" colspan="1" style="width: 0px;" ria-sort="descending">'.$key.'</th>';
                }

                $thead = '<thead><tr role="row">'.$cols.'</tr></thead>';

                foreach ($object->details as $detail) {



                    $class = $isAlterRow ? "odd" : "even";

                    $rows .= '<tr role="row" class="'.$class.'">';

                    $totalService = ($detail->totalModified == "0") ? $detail->total : $detail->totalModified;

                    $rows .= '<td class="ng-scope">'.$detail->service->name.'</td>';
                    $rows .= '<td class="ng-scope">'.$detail->service->unitValue.'</td>';
                    $rows .= '<td class="ng-scope">'.$detail->quantity.'</td>';
                    $rows .= '<td class="ng-scope">'.$totalService.'</td>';
                    $rows .= '<td class="ng-scope"><a href="'.$detail->service->url.'">Ver detalle servicio</a></td>';

                    $rows .= '</tr>';

                    $isAlterRow = !$isAlterRow;

                }
                $tbody = '<tbody>'.$rows.'</tbody>';
                $html = '<div id="dtPollResultOptions_wrapper" class="dataTables_wrapper form-inline no-footer"><table datatable="" dt-options="dtPollResultOptions" id="dtPollResultOptions" dt-columns="dtPollResultColumns" class="table table-bordered table-hover ng-isolate-scope no-footer dataTable" style="display: table; width: 100%;" role="grid" aria-describedby="dtPollResultOptions_info">'.$thead.$tbody.'</table></div>';
            }

            foreach ($object->responsible as $responsible) {
                if ($responsible->isActive) {
                    $params['Fecha'] = $object->deadline;
                    $params['Servicios'] = strip_tags ($html);
                    $params['Total'] = $object->totalModified;

                    //Mail::sendTo("david.blandon@gmail.com", 'rainlab.user::mail.new_quote', $params);
                    Mail::sendTo($responsible->email, 'rainlab.user::mail.new_quote', $params);
                }

            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
        }


        return Quote::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1") {

        // parse model
        if ($model) {
            $this->setInfo($model, $fmt_response);
        }

        return $this;
    }

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoSummary($model);
                break;
            case "3":
                $this->getBasicInfoResponislbe($model);
                break;
            default:
                $this->getBasicInfo($model);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1") {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof Quote) {
                    $parsed[] = (new QuoteDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new QuoteDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof Quote) {
            return (new QuoteDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new QuoteDTO();
            }
        }
    }

    private function getUserSsession() {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false) {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
