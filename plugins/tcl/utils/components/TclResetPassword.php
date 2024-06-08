<?php

namespace Tcl\Utils\Components;

use Auth;
use Cms\Classes\ComponentBase;
use Mail;
use October\Rain\Support\ValidationException;
use RainLab\Translate\Models\Message;
use System\Classes\ApplicationException;
use Validator;

class TclResetPassword extends ComponentBase {

    public function componentDetails() {
        return [
            'name' => 'rainlab.user::lang.reset_password.reset_password',
            'description' => 'rainlab.user::lang.reset_password.reset_password_desc'
        ];
    }

    public function defineProperties() {
        return [
            'paramCode' => [
                'title' => 'rainlab.user::lang.reset_password.code_param',
                'description' => 'rainlab.user::lang.reset_password.code_param_desc',
                'type' => 'string',
                'default' => 'code'
            ]
        ];
    }

    /**
     * Trigger the password reset email
     */
    public function onRestorePassword() {
        $data = post();

        $rules = [
            'email' => 'required|email|min:2|max:32',
            'recaptcha_response_field' => 'required',
        ];

        $validation = Validator::make($data, $rules);
        if ($validation->fails())
            throw new ValidationException($validation);

        // Captcha Validation
        $challenge = app('Input')->get('recaptcha_challenge_field');
        $cpatchaval = app('Input')->get('recaptcha_response_field');

        $isValidCaptcha = $this->check($challenge, $cpatchaval);

        if (!$isValidCaptcha) {
            $rs["code"] = "M14";
            $rs["result"] = $this->translateString("Captcha invalid...");
            throw new ValidationException($rs);
        }

        if (!($user = Auth::findUserByLogin(post('email')))){
            Log::info("P#");
            throw new ApplicationException($this->translateString('a.user.was.not.found.with.the.given.credentials'));
        }
        
        if(!$user->is_activated){
             Log::info("P#");
            throw new ApplicationException($this->translateString('a.user.was.not.found.with.the.given.credentials'));
        }
        
        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);

        $params = array();
        //$params[$this->property('paramCode')] = $code;
        $params[$this->property('paramCode')] = $code;

        $link = $this->pageUrl("site/reset", $params);

        /*
          $link = $this->controller->currentPageUrl([
          $this->property('paramCode') => $code
          ]); */

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('rainlab.user::mail.restore', $data, function($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });
    }

    /**
     * Perform the password reset
     */
    public function onResetPassword() {
        $rules = [
            'code' => 'required',
            'password' => 'required|min:2'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails())
            throw new ValidationException($validation);

        // Captcha Validation
        $challenge = app('Input')->get('recaptcha_challenge_field');
        $cpatchaval = app('Input')->get('recaptcha_response_field');

        $isValidCaptcha = $this->check($challenge, $cpatchaval);

        if (!$isValidCaptcha) {
            $rs["code"] = "M14";
            $rs["result"] = $this->translateString("Captcha invalid...");
            throw new ValidationException($rs);
        }


        /*
         * Break up the code parts
         */
        $parts = explode('!', post('code'));
        if (count($parts) != 2)
            throw new ValidationException(['code' => trans('rainlab.user::lang.account.invalid_activation_code')]);

        list($userId, $code) = $parts;

        if (!strlen(trim($userId)) || !($user = Auth::findUserById($userId)))
            throw new ApplicationException(trans('rainlab.user::lang.account.invalid_user'));

        if (!$user->attemptResetPassword($code, post('password')))
            throw new ValidationException(['code' => trans('rainlab.user::lang.account.invalid_activation_code')]);
    }

    /**
     * Returns the reset password code from the URL
     * @return string
     */
    public function code() {
        $routeParameter = $this->property('paramCode');
        return $this->param($routeParameter);
    }

    public function translateString($string, $params = []) {
        return Message::trans($string, $params);
    }

    /**
     * Call out to reCAPTCHA and process the response
     * @param string $challenge
     * @param string $response
     * @return array(bool, string)
     */
    public function check($challenge, $response) {

        $privatekey = app('config')->get('recaptcha::private_key');
        $ipserver = app('request')->getClientIp();

        $resp = $this->recaptcha_check_answer($privatekey, $ipserver, $challenge, $response);

        return $resp->is_valid;
    }

    /**
     * Submits an HTTP POST to a reCAPTCHA server
     * @param string $host
     * @param string $path
     * @param array $data
     * @param int port
     * @return array response
     */
    private function _recaptcha_http_post($host, $path, $data, $port = 80) {

        $req = $this->_recaptcha_qsencode($data);

        $http_request = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        $response = '';
        if (false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) )) {
            die('Could not open socket');
        }

        fwrite($fs, $http_request);

        while (!feof($fs))
            $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);

        return $response;
    }

    /**
     * Encodes the given data into a query string format
     * @param $data - array of string elements to be encoded
     * @return string - encoded request
     */
    private function _recaptcha_qsencode($data) {
        $req = "";
        foreach ($data as $key => $value)
            $req .= $key . '=' . urlencode(stripslashes($value)) . '&';

// Cut the last '&'
        $req = substr($req, 0, strlen($req) - 1);
        return $req;
    }

    /**
     * Calls an HTTP POST function to verify if the user's guess was correct
     * @param string $privkey
     * @param string $remoteip
     * @param string $challenge
     * @param string $response
     * @param array $extra_params an array of extra variables to post to the server
     * @return ReCaptchaResponseObj
     */
    private function recaptcha_check_answer($privkey, $remoteip, $challenge, $response, $extra_params = array()) {

        $verifyserver = app('config')->get('recaptcha::verifyserver');
        $verifypath = app('config')->get('recaptcha::verifypath');

        if ($privkey == null || $privkey == '') {
            die("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
        }

        if ($remoteip == null || $remoteip == '') {
            die("For security reasons, you must pass the remote ip to reCAPTCHA");
        }



//discard spam submissions
        if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0) {
            $recaptcha_response = new ReCaptchaResponseObj();
            $recaptcha_response->is_valid = false;
            $recaptcha_response->error = 'incorrect-captcha-sol';
            return $recaptcha_response;
        }

        $response = $this->_recaptcha_http_post($verifyserver, $verifypath, array(
            'privatekey' => $privkey,
            'remoteip' => $remoteip,
            'challenge' => $challenge,
            'response' => $response
                ) + $extra_params
        );

        $answers = explode("\n", $response [1]);
        $recaptcha_response = new ReCaptchaResponseObj();

        if (trim($answers [0]) == 'true') {
            $recaptcha_response->is_valid = true;
        } else {
            $recaptcha_response->is_valid = false;
            $recaptcha_response->error = $answers [1];
        }
        return $recaptcha_response;
    }

}

/**
 * A ReCaptchaResponse is returned from recaptcha_check_answer()
 */
class ReCaptchaResponseObj {

    var $is_valid;
    var $error;

}
