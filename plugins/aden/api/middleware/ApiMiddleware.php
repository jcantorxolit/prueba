<?php

namespace AdeN\Api\Middleware;

use Closure;
use App;
use Auth;
use Session;
use AdeN\Api\Classes\AuthClient;
use Illuminate\Http\Request;
use Log;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        try {
            //try to login the api user using OAuth Token in header Authorization
            // The token will be validated by the Secutiry Api Layer
            // Please ensure that apiUrl is correctly parametrized and it is pointing to correct instance domain name.        
            $appAuthorization = $request->header("x-authorization");
            $externalAuthorization = $request->get("x-auth-token");

            if (!$appAuthorization && !$externalAuthorization) {
                return $next($request);
            }

            $isAuth = Auth::check();

            if (!$isAuth) {
                $userInfo = null;

                if ($appAuthorization) {
                    $apiUrl = envi('api_base_url');

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "$apiUrl/auth");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                    $headers = [
                        'Authorization: ' . $appAuthorization,
                    ];

                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    $userInfo = curl_exec($ch);

                    if (curl_errno($ch)) {
                        Log::error("Error checking Auth token : " . curl_error($ch));
                        $userInfo = null;
                    }

                    curl_close($ch);

                    $userInfo = $userInfo ? json_decode($userInfo)->user : null;
                } else if ($externalAuthorization) {
                    $response = AuthClient::getInstance()->checkSession($externalAuthorization);

                    $userInfo = $response && $response->user ? $response->user : null;
                }

                if ($userInfo) {
                    $user = \RainLab\User\Models\User::find($userInfo->id);
                    if ($user) {
                        \Auth::login($user);
                    }
                }
            }

        } catch (\Exception $ex) {
            Log::error($ex);
        }

        return $next($request);

    }

    public function terminate($request, $response)
    {

    }
}
