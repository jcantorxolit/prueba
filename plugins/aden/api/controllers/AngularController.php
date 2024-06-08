<?php

namespace AdeN\Api\Controllers;

use AdeN\Api\Classes\BaseController;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\Router;
use Cms\Classes\Theme;
use Request;
use Response;
use Log;

use Wgroup\Classes\ApiResponse;

class AngularController extends BaseController
{
    public function handle()
    {
        \Log::info('AngularController');

        $url = Request::path();
        $isRedirect = Request::get("redirect", "");

        $isAjax = Request::ajax();

		if ( $isAjax ) {

			Log::info( ":::SESSION TOKEN::::: " . Session::token() );
			Log::info( "X-CSRF-TOKEN::::: " . Request::header( 'X-CSRF-TOKEN' ) );

			//check token CSRF on Ajax Request!
			if ( Session::token() !== Request::header( 'X-CSRF-TOKEN' ) ) {
				// Change this to return something your JavaScript can read...
				throw new Illuminate\Session\TokenMismatchException;
			}
        }

        $isResource = str_contains($url, "plugins")
        || str_contains($url, "api")
        || str_contains($url, "themes");

        if (!$isResource) {

            $theme = Theme::getActiveTheme();
            $router = new Router($theme);

            $pagesAJS = array(
                'app',
                'login',
            );

            $isangular = false;
            foreach ($pagesAJS as $strname) {
                if (strstr($strname, $url) || strstr($url, $strname)) {
                    $isangular = true;
                    break;
                }
            }

            $url = !starts_with($url, "/") ? "/" . $url : $url;
            $page = $router->findByUrl($url);

            if (!$page || $isangular) {

                $controller = new CmsController($theme);

                if ($isAjax) {

                    if ($isRedirect == "") {

                        //$mss = Request::get("locale","g");
                        $octoberRequest = Request::header('X-OCTOBER-REQUEST-HANDLER');

                        if ($octoberRequest || $octoberRequest != "") {
                            return $controller->run('/app/clientes/list');
                        }

                        // is API Restful
                        $response = new ApiResponse();
                        $response->setErrorcode(404);
                        $response->setMessage("route not found.");
                        $response->setResult(array());

                        return Response::json($response, 404);
                    }
                } else {
                    return $controller->run('/app/clientes/list');
                }
            }
        }
    }
}
