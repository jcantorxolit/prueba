<?php


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Flash;
use ReCaptcha\ReCaptcha;
use Alxy\Captcha\Models\Settings;


use AdeN\Api\Classes\ProxyClient;


//coment
App::before(function ($request) {

    $captchEnabled = Config::get("cms.enableCaptcha", false);

    if ($captchEnabled) {
        if ($request->exists('g-recaptcha-response')) {
            $key = Settings::get('secret_key');

            Log::error("verificando Captcha $key");
            $recaptcha = new ReCaptcha($key);

            /**
             * Verify the reponse, pass user's IP address
             */
            $response = $recaptcha->verify(
                $request->input('g-recaptcha-response'),
                $request->ip()
            );

            /**
             * Fail, if the response isn't OK
             */
            if (!$response->isSuccess()) {
                if ($request->ajax()) {
                    Log::error($response->getErrorCodes());
                    throw new \Exception("Error verificando captcha");
                } else {
                    foreach ($response->getErrorCodes() as $code) {
                        Flash::error($code);
                    }

                    return redirect()->back()->withInput();
                }
            }
        }
    }

    //Route::get('{any}', 'AdeN\Api\Controllers\AngularController@handle')->where('login', 'login.*');
    //Route::get('{app}', 'AdeN\Api\Controllers\AngularController@handle')->where('app', 'app.*');

    // Filtro para interceptar las operaciones y ejecutar la view de angular adecuada.
    // Route::filter('api', function () {
    //     // para restfull services
    //     Event::fire('clockwork.controller.start');

    // });

    // Route::filter( 'proxy', function () {


    //     $proxyEnabled = Config::get( "proxy.enabled", false );

    // 	if ( ! $proxyEnabled ) {
    // 		return;
    //     }

    // 	$isAjax = Request::ajax();

    // 	if ( $isAjax ) {

    // 		// Several filters here:
    // 		// - CSRF
    // 		// - Authentication proxy
    // 		// - Authorization proxy

    // 		//check token CSRF on Ajax Request!
    // 		if ( Session::token() !== Request::header( 'X-CSRF-TOKEN' ) ) {
    // 			Log::error("error on session token");
    // 			// Change this to return something your JavaScript can read...
    // 			return new JsonResponse( [ "error_message" => "UNAUTHORIZED", "error_code" => 401 ], 401 );
    // 		}

    // 		// Check Authentication and Authorization with proxy
    // 		$OauthToken   = Request::cookie( ProxyClient::OATUH_TOKEN );
    // 		$refreshToken = Request::cookie( ProxyClient::REFRESH_TOKEN );

    // 		$proxyClient = ProxyClient::getInstance();
    // 		$result = [];

    // 		try{
    // 			$result      = $proxyClient->checkSession( $OauthToken, $refreshToken );
    // 		}catch (Exception $ex){
    // 			Log::error("error on checkSession");
    // 			// here we should be inform to user and logout it from the system (session expired)
    // 			return new JsonResponse( [ "error_message" => "UNAUTHORIZED", "error_code" => 401 ], 401 );
    // 		}

    // 		// Check Authentication
    // 		if ( ! isset( $result["isAuth"] ) || ! isset( $result["isAuth"]["id"] ) || ! $result["isAuth"] || ! $result["isAuth"]["id"] ) {
    // 			Log::error("error on authentication");
    // 			//will try to refresh with the token refresh on cookie
    // 			return new JsonResponse( [ "error_message" => "UNAUTHORIZED", "error_code" => 401 ], 401 );
    // 		}

    // 		//todo: we will need reload the page after an refreshhh because the cookie maybe is leave old value on the request.. please check!!!
    // 		Log::debug( "OAUTH SESSIONNN AFTER REFRESHHH:::::::::" . $result["isAuth"]["id"] );

    // 		// Check Authorization
    // 		// todo: pendding check permissions to consume an specific module on api (currently the user logged can access to api and the action have the handler of data regarding to user authenticated)

    // 		// if not throw exception, then all is fine.. and the user is consuming the api securily..

    // 	} else {

    //         if ( str_contains(Request::path(), "api/") ) {

    //             $OauthToken   = Request::cookie( ProxyClient::OATUH_TOKEN );
    //             $refreshToken = Request::cookie( ProxyClient::REFRESH_TOKEN );

    //             $proxyClient = ProxyClient::getInstance();
    //             $result = [];

    //             try{
    //                 $result      = $proxyClient->checkSession( $OauthToken, $refreshToken );
    //             }catch (Exception $ex){
    //                 Log::error("error on checkSession");
    //                 // here we should be inform to user and logout it from the system (session expired)
    //                 return new JsonResponse( [ "error_message" => "UNAUTHORIZED", "error_code" => 401 ], 401 );
    //             }

    //             // Check Authentication
    //             if ( ! isset( $result["isAuth"] ) || ! isset( $result["isAuth"]["id"] ) || ! $result["isAuth"] || ! $result["isAuth"]["id"] ) {
    //                 Log::error("error on authentication");
    //                 //will try to refresh with the token refresh on cookie
    //                 return new JsonResponse( [ "error_message" => "UNAUTHORIZED", "error_code" => 401 ], 401 );
    //             }

    //         } else if ( Session::token() !== Input::get( '_token' ) ) {
    //             return new JsonResponse( [ "error_message" => "UNAUTHORIZED", "error_code" => 401 ], 401 );
    //         }
    // 		//throw new Illuminate\Session\TokenMismatchException;
    // 	}

    // });

    // Route::filter('angular', function () {
    //     Event::fire('clockwork.controller.start');

    //     $url = Request::path();
    //     $isRedirect = Request::get("redirect", "");

    //     $isAjax = Request::ajax();

    // 	if ( $isAjax ) {

    // 		Log::info( ":::SESSION TOKEN::::: " . Session::token() );
    // 		Log::info( "X-CSRF-TOKEN::::: " . Request::header( 'X-CSRF-TOKEN' ) );

    // 		//check token CSRF on Ajax Request!
    // 		if ( Session::token() !== Request::header( 'X-CSRF-TOKEN' ) ) {
    // 			// Change this to return something your JavaScript can read...
    // 			throw new Illuminate\Session\TokenMismatchException;
    // 		}
    //     }

    //     $isResource = str_contains($url, "plugins")
    //     || str_contains($url, "api")
    //     || str_contains($url, "themes");

    //     if (!$isResource) {

    //         $theme = Theme::getActiveTheme();
    //         $router = new Router($theme);

    //         $pagesAJS = array(
    //             'app',
    //             'login',
    //         );

    //         $isangular = false;
    //         foreach ($pagesAJS as $strname) {
    //             if (strstr($strname, $url) || strstr($url, $strname)) {
    //                 $isangular = true;
    //                 break;
    //             }
    //         }

    //         $url = !starts_with($url, "/") ? "/" . $url : $url;
    //         $page = $router->findByUrl($url);

    //         if (!$page || $isangular) {

    //             $controller = new CmsController($theme);

    //             if ($isAjax) {

    //                 if ($isRedirect == "") {

    //                     //$mss = Request::get("locale","g");
    //                     $octoberRequest = Request::header('X-OCTOBER-REQUEST-HANDLER');

    //                     if ($octoberRequest || $octoberRequest != "") {
    //                         return $controller->run('/app/clientes/list');
    //                     }

    //                     // is API Restful
    //                     $response = new ApiResponse();
    //                     $response->setErrorcode(404);
    //                     $response->setMessage("route not found.");
    //                     $response->setResult(array());

    //                     return Response::json($response, 404);
    //                 }
    //             } else {
    //                 return $controller->run('/app/clientes/list');
    //             }
    //         }
    //     }
    // });

    // api/save..


    //Matriculamos rutas para Angular
    //Route::when('logout*', 'angular');
    // Route::when( 'login*', 'angular' );
    // Route::when( 'app*', 'angular' );
    // Route::when( 'api*', 'proxy' );

    //Route::group(['prefix' => "api"], function () {
    Route::group(['middleware' => ['\Tymon\JWTAuth\Middleware\GetUserFromToken'], 'prefix' => "api"], function () {

        //----------------------------------------------------------------------------------BUDGET

        require(__DIR__ . '/modules/budget/http/apiRoutes.php');

        require(__DIR__ . '/modules/budget/detail/http/apiRoutes.php');


        //----------------------------------------------------------------------------------CERTIFICATE

        require(__DIR__ . '/modules/certificate/gradeparticipant/http/apiRoutes.php');


        //----------------------------------------------------------------------------------CUSTOMER

        require(__DIR__ . '/modules/customer/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/agent/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/arlservicecost/http/apiRoutes.php');    //ARL;

        require(__DIR__ . '/modules/customer/user/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/document/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/documentsecurity/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/documentsecurityuser/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/economicgroup/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/employee/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/employee/criticalactivity/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/employee/document/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/employee/staging/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/employee/demographicstaging/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/employee/indicators/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/tracking/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/trackingdocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/diagnostic/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/diagnosticprevention/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/management/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/managementdetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/managementdetaildocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandard/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandarditem/http/apiRoutes.php');


        require(__DIR__ . '/modules/customer/evaluationminimumstandard0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandarditem0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandarditemcomment0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandarditemdetail0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandarditemdocument0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandarditemverification0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/evaluationminimumstandardtracking0312/http/apiRoutes.php');


        require(__DIR__ . '/modules/customer/roadsafety/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/roadsafetyitem/http/apiRoutes.php');


        require(__DIR__ . '/modules/customer/roadsafety40595/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/roadsafety40595/container/roadsafetyitem40595/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/roadsafety40595/container/roadsafetyitemcomment40595/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/roadsafety40595/container/roadsafetyitemdetail40595/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/roadsafety40595/container/roadsafetyitemdocument40595/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/roadsafety40595/container/roadsafetyitemverification40595/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/roadsafety40595/container/roadsafetytracking40595/http/apiRoutes.php');


        require(__DIR__ . '/modules/customer/contractor/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractdetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractdetailcomment/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractdetaildocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractsafetyinspection/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractsafetyinspectionheaderfield/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractsafetyinspectionlistitem/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractsafetyinspectionlistitemcomment/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractsafetyinspectionlistitemdocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/contractsafetyinspectionlistobservation/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/safetyinspection/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/safetyinspectionlist/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/safetyinspectionlistobservation/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/safetyinspectionlistitem/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/safetyinspectionlistitemcomment/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/safetyinspectionlistitemdocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/safetyinspectionheaderfield/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/absenteeismdisability/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/absenteeismdisabilitydaycharged/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/absenteeismdisabilitystaging/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/absenteeismindicator/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/occupationalreportal/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/occupationalreportaldocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/occupationalreportincident/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/occupationalinvestigational/http/apiRoutes.php');
        
        require(__DIR__ . '/modules/customer/occupationalinvestigationaldocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/occupationalinvestigationalresponsible/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configactivity/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configactivitystaging/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configjobprocess/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configmacroprocess/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configmacroprocessstaging/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configprocess/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configprocessstaging/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configworkplace/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configworkplace/shiftschedule/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configworkplace/shiftscheduledetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configworkplace/shiftscheduledetailemployee/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configprocessexpress/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configprocessexpressrelation/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configactivityexpress/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configactivityexpressrelation/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configjobexpress/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configjobexpressrelation/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configactivityprocess/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configquestionexpress/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configquestionexpresshistorical/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configquestionexpressintervention/http/apiRoutes.php');



        //COVID 19
        require(__DIR__ . '/modules/customer/covid/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/covid/daily/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/covid/dailypersonintouch/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/covid/dailytemperature/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/covid/dailypersonnear/http/apiRoutes.php');


        // MANACLES
        require(__DIR__ . '/modules/customer/manacle/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/manacleemployee/http/apiRoutes.php');


        // VR EMPLOYEE
        require(__DIR__ . '/modules/customer/vremployee/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/vremployee/experience/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/vremployee/experienceanswer/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/vremployee/sceneanswer/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/vremployee/experienceevaluation/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/vremployee/satisfactionindicators/http/apiRoutes.php');

        // VR GENERAL OBSERVATION
        require(__DIR__ . '/modules/customer/vrgeneralobservation/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/vrsignaturecertificate/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configjobactivity/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configjobactivitystaging/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configactivityhazard/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configjobactivityhazardrelation/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/configjobactivitydocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/improvementplan/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/improvementplancomment/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/improvementplandocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/improvementplanactionplan/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/audit/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagediagnosticsource/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagediagnosticsourcedetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagerestriction/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagerestrictiondetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagerestrictiondocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationsource/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationsourcedocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationsourceopportunitydetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationsourceregionaldetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationsourcenationaldetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationsourcejusticedetail/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationlost/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagequalificationlostdocument/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamageadministrativeprocess/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/healthdamagerestrictionobservation/http/apiRoutes.php');

        //----------------------------------------------------------------------------------REPORT

        require(__DIR__ . '/modules/customer/report/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/reportcalculatedfield/http/apiRoutes.php');

        //----------------------------------------------------------------------------------INVESTIGATION

        require(__DIR__ . '/modules/customer/investigational/http/apiRoutes.php');

        //----------------------------------------------------------------------------------UNSAFE ACTS

        require(__DIR__ . '/modules/customer/unsafeact/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/unsafeactobservation/http/apiRoutes.php');

        //----------------------------------------------------------------------------------MATRIX

        require(__DIR__ . '/modules/customer/matrix/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/matrixdata/http/apiRoutes.php');


        //----------------------------------------------------------------------------------INTERNAL CERTIFICATE

        require(__DIR__ . '/modules/customer/internalcertificategrade/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/internalcertificateprogram/http/apiRoutes.php');



        //----------------------------------------------------------------------------------INTERNAL PROJECT

        require(__DIR__ . '/modules/customer/internalprojectusertask/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/internalprojectcomment/http/apiRoutes.php');

        require(__DIR__ . '/modules/customer/internalprojectdocument/http/apiRoutes.php');



        //----------------------------------------------------------------------------------WORK MEDECINE

        require(__DIR__ . '/modules/customer/workmedicine/http/apiRoutes.php');


        //----------------------------------------------------------------------------------CUSTOMER PARAMETERS

        require(__DIR__ . '/modules/customer/parameter/http/apiRoutes.php');


        //----------------------------------------------------------------------------------  JOB CONDITIONS
        require(__DIR__ . '/modules/customer/jobconditions/jobcondition/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/jobconditions/evaluation/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/jobconditions/jobcondition/staging/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/jobconditions/indicator/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/jobconditions/intervention/http/apiRoutes.php');


        //----------------------------------------------------------------------------------CUSTOMER LICENSES
        require(__DIR__ . '/modules/customer/licenses/http/apiRoutes.php');



        //----------------------------------------------------------------------------------EMPLOYEE INFORMATION

        require(__DIR__ . '/modules/employeeinformationdetail/http/apiRoutes.php');



        //----------------------------------------------------------------------------------  POSITIVA FGN
        require(__DIR__ . '/modules/positivafgn/consultant/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/consultant/sectional/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/campus/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/vendor/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/vendor/maincontact/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/vendor/coverage/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/vendor/contract/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/gestpos/task/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/gestpos/activity/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/gestpos/activity/associatedtask/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/gestpos/activity/evidence/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/fgn/config/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/fgn/activity/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/fgn/activityconfig/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/fgn/activityconfigsectional/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/fgn/activityconfigsectional/configconsultant/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/management/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/indicator/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/sectional/http/apiRoutes.php');
        require(__DIR__ . '/modules/positivafgn/professional/http/apiRoutes.php');


        //----------------------------------------------------------------------------------DASHBOARD
        require(__DIR__ . '/modules/dashboard/topmanagement/http/apiRoutes.php');
        require(__DIR__ . '/modules/dashboard/commercial/http/apiRoutes.php');



        //----------------------------------------------------------------------------------MINIMUM STANDARD 0312

        require(__DIR__ . '/modules/minimumstandard0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/minimumstandard0312/minimumstandarditem0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/minimumstandard0312/minimumstandarditemcriterion0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/minimumstandard0312/minimumstandarditemcriteriondetail0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/minimumstandard0312/minimumstandarditemdetail0312/http/apiRoutes.php');

        require(__DIR__ . '/modules/minimumstandard0312/minimumstandarditemquestion0312/http/apiRoutes.php');



        //----------------------------------------------------------------------------------RESOURCE LIBRARY
        require(__DIR__ . '/modules/resourcelibrary/http/apiRoutes.php');


        //----------------------------------------------------------------------------------TEMPLATE MANAGE
        require(__DIR__ . '/modules/templatemanage/http/apiRoutes.php');


        //----------------------------------------------------------------------------------REPORT
        require(__DIR__ . '/modules/report/http/apiRoutes.php');


        //----------------------------------------------------------------------------------USER MESSAGE
        require(__DIR__ . '/modules/user/message/http/apiRoutes.php');
        require(__DIR__ . '/modules/user/http/apiRoutes.php');


        //----------------------------------------------------------------------------------DISABILITY DIAGNOSTIC

        require(__DIR__ . '/modules/disabilitydiagnostic/http/apiRoutes.php');



        //----------------------------------------------------------------------------------ECONOMIC SECTOR

        require(__DIR__ . '/modules/economicsector/http/apiRoutes.php');
        require(__DIR__ . '/modules/economicsector/task/http/apiRoutes.php');


        //----------------------------------------------------------------------------------CONFIG PROGRAM | ECONOMIC SECTOR
        require(__DIR__ . '/modules/programmanagement/economicsector/http/apiRoutes.php');

        //---------------------------------------------------------------------------------- PROJECTS
        require(__DIR__ . '/modules/project//http/apiRoutes.php');
        require(__DIR__ . '/modules/project/documents/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/projectcomments/http/apiRoutes.php');
        require(__DIR__ . '/modules/customer/projecthistorial/http/apiRoutes.php');


        //---------------------------------------------------------------------------------- CONTRIBUTIONS
        require(__DIR__ . '/modules/customer/contributions/http/apiRoutes.php');


        //----------------------------------------------------------------------------------CONFIG HELP ROLES PROFILES
        require(__DIR__ . '/modules/helprolesprofiles/http/apiRoutes.php');


        //----------------------------------------------------------------------------------CONFIGS
        require(__DIR__ . '/modules/config/signaturecertificatevr/http/apiRoutes.php');

        require(__DIR__ . '/modules/config/signatureindicatorvr/http/apiRoutes.php');


        //----------------------------------------------------------------------------------OLMED
        require(__DIR__ . '/modules/olmed/http/apiRoutes.php');



        Route::match(['get', 'post'], 'list', 'AdeN\Api\Controllers\ListController@index');
        Route::match(['get', 'post'], 'chart', 'AdeN\Api\Controllers\ChartController@index');


        require(__DIR__ . '/legacyRoutes.php');
    });

    Route::group(['prefix' => "public/api"], function () {
        Route::match(['get', 'post'], 'list', 'AdeN\Api\Controllers\PublicController@index');
        Route::post('customer/find', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@find');
        Route::post('customer/sign-up', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@signUp');
    });

    Route::get('logout', 'Wgroup\Controllers\UserController@logout');
});

App::after(function ($request, $response) {
    Event::fire('clockwork.controller.end');
});
