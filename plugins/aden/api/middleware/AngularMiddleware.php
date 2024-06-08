<?php

namespace AdeN\Api\Middleware;

use Closure;
use Cms\Classes\CmsController;
use Cms\Classes\Theme;
use Cms\Classes\Router;
use Response;
use Log;
use Session;

class AngularMiddleware
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
        \Log::info(json_encode(Session::get('october_session')));
        return $next($request);
    }

    public function terminate($request, $response)
    {
        \Log::info('AngularMiddleware::' . $request->path());
        //youre code
        if (starts_with($request->path(), "app") || starts_with($request->path(), "login")) {

            \Log::info('AngularMiddleware');

            $url = $request->path();
            $isRedirect = $request->get("redirect", "");

            $isAjax = $request->ajax();

            if ($isAjax) {

                Log::info(":::SESSION TOKEN::::: " . Session::token());
                Log::info("X-CSRF-TOKEN::::: " . $request->header('X-CSRF-TOKEN'));

                //check token CSRF on Ajax Request!
                if (Session::token() !== $request->header('X-CSRF-TOKEN')) {
                    throw new Illuminate\Session\TokenMismatchException;
                }
            }

            $isResource = str_contains($url, "plugins")
                || str_contains($url, "api")
                || str_contains($url, "themes");

            if (!$isResource) {
                \Log::info('AngularMiddleware isResource');
                $theme = Theme::getActiveTheme();
                $router = new Router($theme);

                $pagesAJS = array(
                    'app',
                    'login',
                );

                $isAngular = false;
                foreach ($pagesAJS as $page) {
                    if (strstr($page, $url) || strstr($url, $page)) {
                        $isAngular = true;
                        break;
                    }
                }

                $url = !starts_with($url, "/") ? "/" . $url : $url;
                $page = $router->findByUrl($url);

                if (!$page || $isAngular) {
                    \Log::info('AngularMiddleware isAngular');
                    $controller = new CmsController($theme);

                    if ($isAjax) {

                        if ($isRedirect == "") {

                            //$mss = $request->get("locale","g");
                            $octoberRequest = $request->header('X-OCTOBER-REQUEST-HANDLER');

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
                        \Log::info('AngularMiddleware !isAngular..');
                        return $controller->run('/app/clientes/list');
                    }
                }
            }
        }

        // Store the session data...
    }
}
