<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
use Controller as BaseController;
use Exception;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Response;
use Session;
use System\Models\Parameters;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\ServiceApi;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use Wgroup\CustomerUser\CustomerUser;
use Wgroup\CustomerUser\CustomerUserDTO;
use Wgroup\CustomerUser\CustomerUserService;
use Wgroup\CustomerUserSkill\CustomerUserSkill;
use Wgroup\Models\Customer;
use Wgroup\SystemParameter\SystemParameter;
use PDF;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Wgroup\SystemParameter\SystemParameterDTO;
use RainLab\User\Models\Settings as UserSettings;
use Mail;
use October\Rain\Support\ValidationException;
use DB;
use Validator;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerUserController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
    private $serviceCustomer;
    private $request;
    private $user;
    private $response;
    protected $groupStatusCache = false;
    protected $selectedFilesCache = false;

    /**
     * @var string Message to display when there are no records in the list.
     */
    public $noRecordsMessage = 'No files found';

    /**
     * @var string Message to display when the Delete button is clicked.
     */
    public $deleteConfirmation = 'Do you really want to delete selected files or directories?';

    /**
     * @var array A list of default allowed file types.
     * This parameter can be overridden with the cms.allowedAssetTypes configuration option.
     */
    public $allowedAssetTypes = ['jpg', 'jpeg', 'bmp', 'png', 'gif', 'css', 'js', 'woff', 'svg', 'ttf', 'eot', 'json', 'md', 'less', 'sass', 'scss'];

    public function __construct()
    {

        //set service
        $this->service = new CustomerUserService();
        $this->serviceCustomer = new ServiceApi();
        $this->translate = Translator::instance();

        // set user
        $this->user = $this->user();

        // @todo validate user and permisions
        // set request
        $this->request = app('Input');

        // set response
        $this->response = new ApiResponse();
        $this->response->setMessage("1");
        $this->response->setStatuscode(200);
    }


    public function index()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            //Si es un usuario de un cliente
            $user = $this->user();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    $customerId = -1;
                }
            }


            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);

            // Counts
            $recordsTotal = $this->service->getCount("", $customerId);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerUserDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function save()
    {
        $text = $this->request->get("data", "");

        try {
            $json = base64_decode($text);

            $info = json_decode($json);

            if (!CustomerUserDTO::canCreate($info)) {
                throw new \Exception('El E-mail ya se encuentra en uso');
            }

            // Parse to model
            $currentId = $info->id;

            if (CustomerUserDTO::validateEmail($info)) {

                $model = CustomerUserDTO::fillAndSaveModel($info);

                // Parse to send on response
                $result = CustomerUserDTO::parse($model);

                if ($currentId == 0) {

                    $password = $this->generatePassword(5);

                    $userData = array(
                        'name' => $info->firstName . ' ' . $info->lastName,
                        'email' => $info->email,
                        'password' => $password,
                        'password_confirmation' => $password
                    );

                    $type = $info->profile ? $info->profile->value : 'customerUser';

                    try {
                        $this->onRegister($userData, $info->customerId, $type);
                    } catch (Exception $ex) {
                        Log::error($ex);
                    }

                    $newUser = User::findByEmail($info->email);

                    if ($newUser != null) {
                        $model->user_id = $newUser->id;
                        $model->save();

                        //DAB->20200717: SPRINT 15
                        //REMOVE UPDATE USER ROLE BASE ON CUSTOMER SIZE
                        //$customer = Customer::find($info->customerId);
                        //$this->updateUserRole($customer, $newUser);
                    }
                } else {
                    $userEntity = User::find($model->user_id);

                    if ($userEntity != null) {
                        $userEntity->wg_type = $info->profile ? $info->profile->value : 'customerUser';
                        $userEntity->is_activated = $model->isActive;
                        $userEntity->save();
                    }
                }

                $this->response->setResult($result);
            } else {
                throw new \Exception('El E-mail ya se encuentra en uso');
            }


        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc);

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    private function updateUserRole($customer, $newUser)
    {
        if ($customer != null) {

            if ($customer->size != null) {

                $roleName = '';

                switch ($customer->size) {
                    case "MC":
                        $roleName = "MICRO";
                        break;
                    case "PQ":
                        $roleName = "PEQUEÑA";
                        break;
                    case "MD":
                        $roleName = "MEDIANA";
                        break;
                    case "GD":
                        $roleName = "GRANDE";
                        break;
                }

                if ($roleName != '') {
                    $role = DB::table('shahiemseymor_roles')->where('name', $roleName)->first();

                    if ($role != null) {
                        DB::table('shahiemseymor_assigned_roles')->insert(
                            ['user_id' => $newUser->id, 'role_id' => $role->id]
                        );
                    }
                }
            }
        }
    }

    private function getRandomBytes($nbBytes = 32)
    {
        $bytes = openssl_random_pseudo_bytes($nbBytes, $strong);
        if (false !== $bytes && true === $strong) {
            return $bytes;
        } else {
            throw new \Exception("Unable to generate secure token from OpenSSL.");
        }
    }

    private function generatePassword($length)
    {
        return substr(preg_replace("/[^a-zA-Z0-9]/", "", base64_encode($this->getRandomBytes($length + 1))), 0, $length);
    }

    public function delete()
    {
        $id = $this->request->get("id", "0");

        try {
            if (!($model = CustomerUser::find($id))) {
                throw new Exception("Record not found to delete.");
            }

            $userEntity = User::find($model->user_id);

            if ($userEntity != null) {
                $userEntity->is_activated = 0;
                $userEntity->save();
            }

            $model->delete();

            $this->response->setResult(1);
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function skillDelete()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {
            if (!($model = CustomerUserSkill::find($id))) {
                throw new Exception("Customer not found to delete.");
            }

            $model->delete();

            $this->response->setResult(1);
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function get()
    {

        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerUser::find($id))) {
                throw new \Exception("Record not found", 404);
            }

            //Get data
            $result = CustomerUserDTO::parse($model);

            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            if ($exc->getCode()) {
                $this->response->setStatuscode($exc->getCode());
            } else {
                $this->response->setStatuscode(500);
            }
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function onRegister($data, $customerId, $type = 'customerAdmin')
    {
        /*
         * Validate input
         */

        if (!array_key_exists('password_confirmation', $data))
            $data['password_confirmation'] = $data['password'];

        $rules = [
            'email' => 'required|email|between:2,64',
            'password' => 'required|min:2'
        ];

        $loginAttribute = UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
        if ($loginAttribute == UserSettings::LOGIN_USERNAME)
            $rules['username'] = 'required|between:2,64';

        $validation = Validator::make($data, $rules);
        if ($validation->fails())
            throw new ValidationException($validation);

        /*
         * Register user
         */
        $requireActivation = UserSettings::get('require_activation', true);
        $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
        $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;

        try {
            $user = Auth::register($data, $automaticActivation);

        } catch (\Exception $ex) {
            Log::error($ex);
            $user = User::findByEmail($data['email']);
        }

        $user->wg_type = $type;
        $user->company = $customerId;
        $user->save();

        $user->attemptActivation($user->activation_code);
    }

    /**
     * Update the user
     */
    public function onUpdate($user, $data)
    {

        $user->save($data);

        /*
         * Password has changed, reauthenticate the user
         */
        if (strlen(post('password'))) {
            Auth::login($user->reload(), true);
        }
    }

    protected function sendActivationEmail($user)
    {
        $code = implode('!', [$user->id, $user->getActivationCode()]);
        $link = $code;

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('rainlab.user::mail.activate', $data, function ($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }

    public function download()
    {
        try {

            $model = SystemParameter::getFirstByGroup('wg_term_condition');

            if ($model != null) {
                $report = array(
                    'title' => $model->item,
                    'date', Carbon::now('America/Bogota')->format('d/m/Y H:m'),
                    'data' => nl2br($model->value)
                    //'data' => $model->value
                );

                $pdf = SnappyPdf::loadView("aden.pdf::html.licence", $report)->setPaper('A4')->setOrientation('portrait')->setWarnings(false);
                return $pdf->download('Licencia_uso.pdf');
            }

        } catch (Exception $exc) {
            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function downloadPrivacy()
    {
        try {

            $model = SystemParameter::getFirstByGroup('wg_privacy_policy');

            if ($model != null) {
                $report = array(
                    'title' => $model->item,
                    'date', Carbon::now('America/Bogota')->format('d/m/Y H:m'),
                    'data' => nl2br($model->value)
                    //'data' => $model->value
                );

                $pdf = SnappyPdf::loadView("aden.pdf::html.privacy-policy", $report)->setPaper('A4')->setOrientation('portrait')->setWarnings(false);
                return $pdf->download('Tratamiento_Datos_Personales.pdf');
            }

        } catch (Exception $exc) {
            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    /**
     *  PRIVATED METHODS
     */

    /**
     * Returns the logged in user, if available
     */
    private function user()
    {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }

    public function loadLocaleFromSession()
    {

        if ($sessionLocale = $this->getSessionLocale()) {
            return $sessionLocale;
        } else {
            if ($localeNegotiated = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $localeNegotiated = substr($localeNegotiated, 0, 2);
                return $localeNegotiated;
            }
        }
    }

    protected function getSessionLocale()
    {
        if (!Session::has(self::SESSION_LOCALE))
            return null;

        return Session::get(self::SESSION_LOCALE);
    }

    // Metdos pilotos
    private function random_numbers($digits)
    {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return mt_rand($min, $max);
    }

    private function download_file($url, $path)
    {

        $newfilename = $path;
        $file = fopen($url, "rb");
        if ($file) {
            $newfile = fopen($newfilename, "wb");

            if ($newfile)
                while (!feof($file)) {
                    fwrite($newfile, fread($file, 1024 * 8), 1024 * 8);
                }
        }

        if ($file) {
            fclose($file);
        }
        if ($newfile) {
            fclose($newfile);
        }
    }

    function debug($message, $param = null)
    {
        if (!$param) {
            //Log::info($message);
        } else if (is_array($param)) {
            //Log::info(vsprintf($message, $param));
        } else {
            //Log::info(sprintf($message, $param));
        }
    }
}
