<?php

namespace Wgroup\Traits;

use BackendAuth;
use Log;
use DB;
use Flash;
use Auth;
use Session;
use Wgroup\Models\Agent;
use Wgroup\Models\Customer;
use Wgroup\NephosIntegration\NephosIntegration;

trait UserSecurity
{
    private $currentUser;

    public function run()
    {
        if ($this->check()) {
            $this->currentUser = $this->getAuthUser();
        } else {
            throw new \Exception('User not logged');
        }
    }

    public function isUserAdmin()
    {
        return $this->currentUser->wg_type == 'system';
    }

    public function isUserAgent()
    {
        return $this->currentUser->wg_type == 'agent';
    }

    public function isUserAgentExternal()
    {
        if ($this->currentUser->wg_type == 'agent') {
            $agent = Agent::whereUserId($this->currentUser->id)->first();
            if ($agent != null) {
                return $agent->type == "02";
            }
        }
        return false;
    }

    public function isUserAgentEmployee()
    {
        if ($this->currentUser->wg_type == 'agent') {
            $agent = Agent::whereUserId($this->currentUser->id)->first();
            if ($agent != null) {
                return $agent->type == "01";
            }
        }
        return false;
    }

    public function isUserRelatedAgent()
    {
        if ($this->currentUser->wg_type == 'agent') {
            $agent = Agent::whereUserId($this->currentUser->id)->first();
            if ($agent != null) {
                return $agent->id;
            }
        }
        return 0;
    }

    public function isUserCustomerAdmin()
    {
        return $this->currentUser->wg_type == 'customerAdmin';
    }

    public function isUserCustomerAgent()
    {
        return $this->currentUser->wg_type == 'customerAgent' || $this->currentUser->wg_type == 'customerUser';
    }

    public function isUserExternalCustomer()
    {
        return $this->currentUser->wg_type == 'externalCustomer';
    }

    public function isUserParticipant()
    {
        return $this->currentUser->wg_type == 'participant';
    }

    public function getUserRelatedCustomer()
    {
        return $this->currentUser->company;
    }

    public function canOpenCustomer($customerId)
    {
        $canOpen  = $this->isUserAdmin() || $this->isUserAgent();

        if ($this->isUserCustomerAdmin() || $this->isUserCustomerAgent())
        {
            $canOpen = $this->getUserRelatedCustomer() == $customerId;

            if (!$canOpen) {
                //$relatedCustomers = $this->getUserRelatedCustomers($this->getUserRelatedCustomer());
                $relatedCustomers = $this->getUserRelatedCustomers();
                $canOpen = in_array($customerId, array_column($relatedCustomers, 'customerId'));
            }
        }

        return $canOpen;
    }

    public function getUserRelatedCustomers()
    {
        $customers = array();

        if ($this->isUserCustomerAdmin() || $this->isUserCustomerAgent())
        {
            $customers = Customer::getRelatedCustomers($this->currentUser->company);

            $customers = json_decode(json_encode($customers), true);
        }

        return $customers;
    }

    public function getUrlToRedirect()
    {

    }

    public function getCurrentUserId()
    {
        return $this->currentUser->id;
    }

    public function check()
    {
        return true;//Auth::check();
    }

    private function getAuthUser()
    {
        if (!Auth::getUser())
            return null;


        return Auth::getUser();
    }

    public function getRedirectUrl($user = null)
    {
        $user = $user ? $user : $this->getAuthUser();

        if ($user == null) {
            return 'logout';
        }

        $redirectUrl = "app/clientes/list";

        if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser")
        {
            $redirectUrl = "app/clientes/view/". $user->company;

            $nephos = NephosIntegration::where('adminUser', $user->email)->first();

            if ($nephos == null) {
                $customer = Customer::find($user->company);

                if ($customer != null) {
                    if ($customer->classification == "Contratante") {
                        $redirectUrl = "app/clientes/list";
                    } else if ($customer->hasEconomicGroup == 1) {
                        $redirectUrl = "app/clientes/list";
                    }
                }
            } else {
                if ($nephos->customer_id == null || $nephos->customer_id = '') {
                    $redirectUrl = "app/enrollment/create";
                } else {
                    $customer = Customer::find($user->company);

                    if ($customer != null) {
                        if ($customer->is_remove == 1) {
                            Flash::success("Lo sentimos la instancia ha sido removida");
                            $redirectUrl = "logout";
                        } else if ($customer->is_disable == 1) {
                            Flash::success("Lo sentimos la instancia ha sido deshabilitada");
                            $redirectUrl = "logout";
                        } else {
                            if ($customer->classification == "Contratante") {
                                $redirectUrl = "app/clientes/list";
                            } else if ($customer->hasEconomicGroup == 1) {
                                $redirectUrl = "app/clientes/list";
                            }
                        }
                    }
                }
            }
        } else if ($user->wg_type == "externalCustomer")
        {
            $redirectUrl = "logout";
        }

        return $redirectUrl;
    }
}
