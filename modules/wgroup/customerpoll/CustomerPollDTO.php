<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerPoll;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Mail;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\Models\Customer;
use RainLab\User\Models\User;
use Wgroup\Models\CustomerDto;
use Wgroup\Poll\Poll;
use Wgroup\Poll\PollDTO;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerPollDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        if ($model) {
            switch ($fmt_response) {
                case "2":
                    $this->getBasicInfoGet($model);
                    break;

                case "3":
                    $this->getBasicInfoCustomer($model);
                    break;

                default:
                    $this->getBasicInfo($model);
            }
        }
    }

    /**
     * @param $model: Modelo CustomerPoll
     */
    private function getBasicInfo($model) {

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->poll = PollDTO::parse($model->poll);
        $this->status = $model->getStatusType();
        $this->answerCount = $model->getAnswerCount();

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoCustomer($model) {

        $this->id = $model->id;
        $this->customer = CustomerDto::parse($model->customer);
        $this->poll = PollDTO::parse($model->poll);
        $this->status = $model->getStatusType();
        $this->answerCount = $model->getAnswerCount();

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoGet($model) {

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->poll = PollDTO::parse($model->getPoll(), "2")[0];
        $this->status = $model->getStatusType();
        $this->answerCount = $model->getAnswerCount();

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }


    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        // Datos de contacto
        if ($object->customers) {
            foreach ($object->customers as $customer) {
                if ($customer) {

                    if ($customer->id) {

                        $entityCustomer = Customer::find($customer->id);

                        if ($entityCustomer != null) {

                            // Existe
                            if (!($customerPoll = CustomerPoll::where('poll_id', $object->poll->id)->where('customer_id', $customer->id)->first())) {
                                // No existe
                                $customerPoll = new CustomerPoll();
                                $customerPoll->customer_id = $customer->id;
                                $customerPoll->poll_id = $object->poll->id;
                                $customerPoll->status = "Asignada";
                                $customerPoll->createdBy = $userAdmn->id;
                                $customerPoll->updatedBy = $userAdmn->id;

                                // Guarda
                                $customerPoll->save();
                            }


                            $childCustomers = [];

                            if ($entityCustomer->hasEconomicGroup == 1) {
                                $childCustomers = Customer::getRelatedCustomers($customer->id);
                            }

                            foreach ($childCustomers as $child) {
                                if (!($customerPoll = CustomerPoll::where('poll_id', $object->poll->id)->where('customer_id', $child->customerId)->first())) {
                                    // No existe
                                    $customerPoll = new CustomerPoll();
                                    $customerPoll->customer_id = $child->customerId;
                                    $customerPoll->poll_id = $object->poll->id;
                                    $customerPoll->status = "Asignada";
                                    $customerPoll->createdBy = $userAdmn->id;
                                    $customerPoll->updatedBy = $userAdmn->id;

                                    // Guarda
                                    $customerPoll->save();
                                }
                            }
                        }
                    }
                }
            }
        }

        return CustomerPoll::find($object->poll->id);
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

    public static function parse($info, $fmt_response = "1") {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerPoll) {
                    $parsed[] = (new CustomerPollDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerPoll) {
            return (new CustomerPollDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerPollDTO();
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
