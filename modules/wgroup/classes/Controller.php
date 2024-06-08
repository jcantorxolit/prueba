<?php

namespace Wgroup\Classes;

use Cms\Classes\Controller as CmsController;
use Cms\Classes\Router;
use Cms\Classes\Theme;
use Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use October\Rain\Support\Facades\Config;
use Request;

class Controller extends BaseController {

    public function validatepage($url = '/') {
        
        if ($url === null)
            $url = Request::path();

        if (!strlen($url))
            $url = '/';

        $theme = Theme::getActiveTheme();
        $router = new Router($theme);

        /*
         * Handle hidden pages
         */
        $page = $router->findByUrl($url);
//Log::info($url);
        /*
         * If the page was not found, render the main page of angular handler
         */
        if (!$page) {

            if (str_contains($url, Config::get('cms.apiUri', 'api'))) {
                // is API Restful
                $response = new ApiResponse();
                $response->setErrorcode(404);
                $response->setMessage("route not found.");
                $response->setResult(array());
                return Response::json($response, 404);
                
            } else {
                $url = '/members';
            }
        }
        
        $controller = new CmsController($theme);
        //Log::info($url);
        //Log::info("RUNNN");
        return $controller->run($url);
    }

}
