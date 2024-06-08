<?php

namespace Tcl\Utils;

use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Modules\Customer\CustomerRepository;
use Backend\Facades\Backend;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Log;
use October\Rain\Database\Model;
use RainLab\User\Components\Account;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\Country;
use RainLab\User\Models\User;
use Session;
use ShahiemSeymor\Roles\Models\UserGroup;
use System\Classes\PluginBase;
use System\Models\Parameter;
use Tcl\Utils\Classes\ClientConverter;
use Wgroup\Models\Agent;
use Wgroup\Models\Rate;
use Wgroup\Models\TemporaryAgency;
use Wgroup\NotifiedAlert\NotifiedAlert;


class Plugin extends PluginBase
{

    public function pluginDetails()
    {
        return [
            'name' => 'Utils',
            'description' => 'Provides some utils.',
            'author' => 'Team Cloud',
            'icon' => 'icon-leaf'
        ];
    }

    public function boot()
    {
        /*
         * Set the page context for translation caching.
         */
        Event::listen('cms.page.beforeDisplay', function ($controller, $url, $page) {

            if (!$page)
                return;
        });

        /*
         * Adds language suffixes to content files.
         */
        Event::listen('cms.page.beforeRenderContent', function ($controller, $fileName) {
        });

        /*
         * Automatically replace form fields for multi lingual equivalents
         */
        Event::listen('backend.form.extendFieldsBefore', function ($widget) {
        });
    }

    public function registerComponents()
    {
        return [
            'Tcl\Utils\Components\CurrencyPicker' => 'currencyPicker',
            'Tcl\Utils\Components\TclLocalePicker' => 'tclLocalPicker',
            'Tcl\Utils\Components\TclResetPassword' => 'tclResetPassword',
        ];
    }

    public function registerSettings()
    {

        return [
            'currencies' => [
                'label' => 'tcl.utils::lang.currency.title',
                'description' => 'tcl.utils::lang.plugin.description',
                'icon' => 'icon-language',
                'url' => Backend::url('tcl/utils/currencies'),
                'order' => 550,
                'category' => 'tcl.utils::lang.plugin.name',
            ],
            'finances' => [
                'label' => 'tcl.utils::lang.finances.title',
                'description' => 'tcl.utils::lang.finances.description',
                'icon' => 'icon-language',
                'url' => Backend::url('tcl/utils/finances'),
                'order' => 550,
                'category' => 'tcl.utils::lang.plugin.name',
            ]
        ];
    }

    public function registerSchedule($schedule)
    {
        // logos
        //$this->runTaskImportLogos($schedule);

        $schedule->call(function () {

            try {

                $model = new NotifiedAlert();

                $model->entityId = 1;
                $model->entityName = "Tracking";
                $model->isSendMail = 1;
                $model->save();
            } catch (\Exception $e) {
                //Log::info($e->getMessage());
                //Log::error($e->getTraceAsString());
            }
        })->everyMinute();

        //$schedule->command('cache:clear')->everyMinute();
    }


    public function registerFormWidgets()
    {
        return [];
    }

    /**
     * Register new Twig variables
     * @return array
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'CUR' => [$this, 'convertCurrency'],
                'FMTCUR' => [$this, 'formatCurrency'],
            ],
            'functions' => [
                'getMinDate' => function () {
                    //return  Carbon::now("America/Bogota");
                    return  Carbon::now();
                },
                'supportHelp' => function () {
                    $currentUser = Auth::getUser();
                    $supportHelp = (new CustomerRepository)->getSupportHelpInformation($currentUser);
                    return json_encode($supportHelp);
                },
                'config' => function ($key, $default = "") {
                    return  Config::get($key, $default);
                },
                'currentUser' => function () {
                    $account = new Account;
                    $user = User::find($account->user()->id);
                    $entityUser = new \stdClass();
                    $entityUser->id = $user->id;
                    $entityUser->name = $user->name;
                    $entityUser->email = $user->email;
                    $entityUser->username = $user->username;
                    $entityUser->wg_type = $user->wg_type;
                    $entityUser->wg_term_condition = $user->wg_term_condition;
                    $entityUser->wg_term_condition_date = $user->wg_term_condition_date;
                    $entityUser->wg_provider = $user->wg_provider;
                    $entityUser->company = $user->company;

                    return json_encode($entityUser);
                },
                'tutorial' => function () {
                    $parameter = Parameter::whereNamespace("wgroup")->whereGroup('tutoial_link')->first();

                    $tutorial = new \stdClass();

                    if ($parameter == null) {
                        $tutorial->value = "http://sylogi.com.co/video.html";
                    } else {
                        $tutorial = $parameter;
                    }

                    return json_encode($tutorial);
                },
                'currentPermissions' => function () {
                    $permissions = [];
                    $account = new Account;
                    $data = User::find($account->user()->id)->allowedPermissions();
//TODO DB
                    //foreach ($roles as $role) {
                        foreach ($data as $perm) {
                            //$permissions[] = str_replace('_', ' ', $perm['code']);
                            $permissions[] = $perm['code'];
                        }
                    //}
                    return json_encode($permissions);
                },
                'currentRoles' => function () {
                    $roles = [];
                    if ($user = $this->user()) {
                        foreach ($user->groups as $rol) {
                            $roles[] = $rol->name;
                        }
                    }
                    return json_encode($roles);
                },
                'countries' => function () {
                    $countries = [];
//TODO DB
                    if ($countriesmodel = \RainLab\Location\Models\Country::isEnabled()->orderBy("name", "asc")->get()) {
                        foreach ($countriesmodel as $country) {
                            $countries[] = $country;
                        }
                    }

                    return json_encode($countries);
                },
                'agents' => function () {
                    $agents = [];

                    if ($agentsmodel = Agent::isEnabled()->orderBy("name", "asc")->get()) {
                        foreach ($agentsmodel as $agent) {
                            $agent->signatureText = "";
                            $agents[] = $agent;
                        }
                    }

                    return json_encode($agents);
                },
                'temporaryAgencies' => function () {
                    $agencies = [];

                    if ($models = TemporaryAgency::getNameList()) {
                        foreach ($models as $country) {
                            $agencies[] = $country;
                        }
                    }

                    return json_encode($agencies);
                },
                'rates' => function () {
                    $rates = [];

                    if ($models = Rate::orderBy("text", "asc")->get()) {
                        foreach ($models as $rate) {
                            //$rates [$rate->id] = $rate;
                            $rates[] = $rate;
                        }
                    }

                    return json_encode($rates);
                },
                'parameters' => function ($type = "all", $orderBy = "id", $typeOrder = "asc") {
                    $params = [];
                    if ($type == "all") {
                        if ($parameters = Parameter::whereNamespace("wgroup")->orderBy($orderBy, $typeOrder)->get()) {
                            foreach ($parameters as $param) {
                                if ($param->group != 'wg_term_condition' && $param->group != 'cube') {
                                    $param->item = preg_replace("/\r|\n/", "", $param->item);
                                    $params[] = $param;
                                }
                            }
                        }
                    } else {
                        if ($parameters = Parameter::whereNamespace("wgroup")->whereGroup($type)->orderBy($orderBy, $typeOrder)->get()) {
                            foreach ($parameters as $param) {
                                $param->item = preg_replace("/\r|\n/", "", $param->item);
                                $params[] = $param;
                            }
                        }
                    }
                    return json_encode($params);
                },
                'groups' => function () {
                    $groups = [];
//TODO DB
                    // if ($models = UserGroup::where('name', 'LIKE', 'GU_%')->orderBy("name", "asc")->get()) {
                    //     foreach ($models as $group) {
                    //         //$rates [$rate->id] = $rate;
                    //         $groups[] = $group;
                    //     }
                    // }

                    return json_encode($groups);
                },
                'availableUserTopManagement' => function() {
                    $account = new Account;
                    $user = User::find($account->user()->id);

                    $emailCurrentUser = $user->email ?? null;
                    $data = envi('TOP_MANAGEMENT_AVAILABLE_USERS') ?? [];

                    return in_array($emailCurrentUser, $data);
                },
                'hasRole' => function ($can) {
                    return UserGroup::hasRole($can);
                },
                'currencyFormat' => [$this, 'convertCurrency'],
                'locale' => [$this, 'getLocale'],
                'priceVipAccess' => [$this, 'getPriceVipAccess'],
                'token' => [$this, 'getTokenSession'],
                'jwtToken' => [$this, 'getJwtTokenSession'],
                'csrf_token' => [$this, 'getCsrfSession'],
                'keysess' => [$this, 'getTokenSessionCoded'],
                'conceptCode' => [$this, 'getLicConceptCode'],
                'vipCode' => [$this, 'getLicVipCode'],
                'bpCode' => [$this, 'getLicBpCode'],
                'currencyFmt' => [$this, 'getCurrencyFormat'],
                'currency' => [$this, 'getCurrency'],
                'urlCancelCart' => [$this, 'getUrlCancelCart'],
                'urlReturnCart' => [$this, 'getUrlReturnCart'],
                'urlNotifyCart' => [$this, 'getUrlNotifyCart'],
                'merchIdPaypalCart' => [$this, 'getMerchIdPaypalCart'],
                'paypalCode' => [$this, 'getPayPalCode'],
                'rowPerPageTables' => [$this, 'getRowPerPageTables'],
                'priceConcept' => [$this, 'getPriceConcept'],
                'minPriceBP' => [$this, 'getMinPriceBP'],
                'priceRenovation' => [$this, 'getPriceRenovationVipAccess'],
                'codeRenovation' => [$this, 'getCodeRenovationVipAccess'],
            ]
        ];
    }

    /**
     * Helper function to replace standard fields with multi lingual equivalents
     * @param  array $fields
     * @param  Model $model
     * @return array
     */
    protected function processFormMLFields($fields, $model)
    {

        foreach ($fields as $name => $config) {

            if (!in_array($name, $model->translatable))
                continue;

            $type = array_get($config, 'type', 'text');
            if ($type == 'text')
                $fields[$name]['type'] = 'mltext';
            elseif ($type == 'textarea')
                $fields[$name]['type'] = 'mltextarea';
        }

        return $fields;
    }

    public function convertCurrency($string, $params = [])
    {
        $converter = ClientConverter::instance();
        return $converter->convert($string);
    }

    public function formatCurrency($val, $params = [])
    {
    }

    public function getLocale()
    {
        return "";
    }

    public function getPriceRenovationVipAccess()
    {
        return Parameter::get('system::licenses.renovation_price', '0');
    }

    public function getCodeRenovationVipAccess()
    {
        return Parameter::get('system::licenses.renovation', '0');
    }

    public function getPriceConcept()
    {
        return Parameter::get('system::licenses.concept_price', '0');
    }

    public function getMinPriceBP()
    {
        return Parameter::get('system::licenses.bp_min_price', '0');
    }

    public function getRowPerPageTables()
    {
        return Parameter::get('system::tables.rowsperpage', '15');
    }

    public function getPriceVipAccess()
    {
        return Parameter::get('system::licenses.vip_price', '0');
    }

    public function getCsrfSession()
    {
        return Session::token();
    }

    public function getTokenSessionCoded()
    {
        return $this->getTokenSession(true);
    }

    public function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }

    public function getJwtTokenSession()
    {
        $token = Session::get('jwtToken');
        return $token;
    }

    public function getCurrencyFormat()
    {
        return "USD";
    }

    public function getCurrency()
    {
        return "US";
    }

    public function getPayPalCode()
    {
        return Parameter::get('system::cart.paypalcode', '');
    }

    public function getUrlCancelCart()
    {
        return Parameter::get('system::cart.urlcancel', '');
    }

    public function getMerchIdPaypalCart()
    {
        return Parameter::get('system::cart.merchidpaypal', '');
    }

    public function getUrlReturnCart()
    {
        return Parameter::get('system::cart.urlreturn', '');
    }

    public function getUrlNotifyCart()
    {
        return Parameter::get('system::cart.urlnotify', '');
    }

    public function getLicConceptCode()
    {
        return Parameter::get('system::licenses.concept', '');
    }

    public function getLicVipCode()
    {
        return Parameter::get('system::licenses.vip', '');
    }

    public function getLicBpCode()
    {
        return Parameter::get('system::licenses.bussinesplan', '');
    }

    private function user()
    {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    private function runTaskImportLogos($schedule)
    {

        $schedule->call(function () {

            try {

                $model = new NotifiedAlert();

                $model->entityId = 1;
                $model->entityName = "Tracking";
                $model->isSendMail = 1;
                $model->save();
            } catch (\Exception $e) {
                Log::info($e->getMessage());
                Log::error($e->getTraceAsString());
            }
        })->everyMinute();
    }
}
