<?php

namespace AdeN\Api;

use AdeN\Api\Classes\LaravelQueryBuilder;
use AdeN\Api\Console\PurgeExportedZipFilesCommand;
use AdeN\Api\Middleware\AngularMiddleware;
use App;
use Backend;
use Cache;
use Event;
use Config;
use Response;
use Illuminate\Database\Schema\Builder;
use System\Classes\PluginBase;
use RainLab\User\Models\User as UserModel;
use System\Models\File as SystemFileModel;
use RainLab\User\Controllers\Users as UsersController;
use Vdomah\JWTAuth\Models\Settings;
/**
 * api Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * @var array Plugin dependencies
     */
    public $require = ['RainLab.User', 'Vdomah.JWTAuth'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'api',
            'description' => 'RESTFul api for SYLOGI',
            'author'      => 'AdeN',
            'icon'        => 'icon-leaf'
        ];
    }

    public function boot()
    {
        Event::listen('migrate.express', 'AdeN\Api\Events\ExpressMatrixMigrationEventHandler');
        Event::listen('migrate.gtc45', 'AdeN\Api\Events\GTC45MatrixMigrationEventHandler');

        Event::listen('rainlab.user.login', function ($user) {
            $data = post();

            try {
                $credentials = [
                    'email'    => $data['email'],
                    'password' => $data['password']
                ];

                $token = \Tymon\JWTAuth\Facades\JWTAuth::attempt($credentials);
                \Session::put('jwtToken', $token);
            } catch (\Exception $ex) {
                \Log::error($ex);
            }

        });

        App::error(function (\October\Rain\Auth\AuthException $exception) {
            return "Usuario o contraseña no válidos.";
        });

        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        // $kernel->pushMiddleware(\October\Rain\Cookie\Middleware\EncryptCookies::class);
        // $kernel->pushMiddleware(\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class);
        $kernel->pushMiddleware(\Illuminate\Session\Middleware\StartSession::class);

        $kernel->pushMiddleware(\AdeN\Api\Middleware\ApiMiddleware::class);

        UserModel::extend(function ($model) {
            $model->addFillable([
                'wg_type'
            ]);

            $model->rules['password'] = 'required:create|between:4,64|confirmed';
            $model->rules['password_confirmation'] = 'required_with:password|between:4,64';
        });

        SystemFileModel::extend(function ($model) {

            /**
             * Returns true if storage.uploads.disk in config/cms.php is "local".
             * @return bool
             */
            $model->addDynamicMethod('isLocalStorage', function () use ($model) {
                return Config::get('cms.storage.uploads.disk') == 'local';
            });

            $model->addDynamicMethod('getTemporaryUrl', function () use ($model) {
                if ($model->isLocalStorage()) {
                    return $model->getPath();
                } else {
                    $disk = $model->getDisk();
                    $expires = now()->addSeconds(Config::get('cms.storage.uploads.temporaryUrlTTL', 3600));
                    if (starts_with($file = $model->getDiskPath(), '/')) {
                        $file = substr($file, 1);
                    }

                    $pathKey = 'system.file:' . $file;
                    $url = Cache::get($pathKey);

                    if (is_null($url)) {
                        $url = Cache::remember($pathKey, $expires, function () use ($disk, $file, $expires) {
                            return $disk->temporaryUrl($file, $expires);
                        });
                    }

                    return $url;
                }
            });



            $model->addDynamicMethod('download', function ($headers = null) use ($model) {
                if ($headers == null) {
                    $headers = [
                        'Content-Type' => $model->content_type,
                        'Content-Description' => 'File Transfer',
                        'Content-Disposition' => "attachment; filename={$model->file_name}",
                        'Content-Transfer-Encoding:binary',
                        'Content-Length:' . $model->file_size,
                        'filename' => $model->file_name
                    ];
                }

                if ($model->isLocalStorage()) {
                    if (is_file($model->getDiskPath())) {
                        Response::download($model->getDiskPath(), $model->file_name, $headers);
                    }
                } else {
                    if ($model->getDisk()->exists($model->getDiskPath())) {
                        $file = $model->getDisk()->get($model->getDiskPath());
                        return response($file, 200, $headers);
                    }
                    return response('Este archivo no existe en el servidor, puede que haya sido una prueba externa!!', 500);
                }
            });



            $model->addDynamicMethod('getStream', function () use ($model) {
                if ($model->isLocalStorage()) {
                    if (is_file($model->getDiskPath())) {
                        return fopen($model->getDiskPath(), "r");
                    }
                } else {
                    if ($model->getDisk()->exists($model->getDiskPath())) {
                        return $model->getDisk()->readStream($model->getDiskPath());
                    }
                    return response('Este archivo no existe en el servidor, puede que haya sido una prueba externa!!', 500);
                }
            });



            $model->addDynamicMethod('getContent', function () use ($model) {
                if ($model->isLocalStorage()) {
                    if (is_file($model->getDiskPath())) {
                        return file_get_contents($model->getDiskPath());
                    }
                } else {
                    if ($model->getDisk()->exists($model->getDiskPath())) {
                        return $model->getDisk()->get($model->getDiskPath());
                    }
                    return response('Este archivo no existe en el servidor, puede que haya sido una prueba externa!!', 500);
                }
            });
        });


        UsersController::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof UserModel) {
                return;
            }

            $form->addTabFields([
                'wg_type' => [
                    'label'   => 'type',
                    'type'    => 'dropdown',
                    'tab'     => 'rainlab.user::lang.user.details',
                    'placeholder'     => '-- select type --',
                    'options' => [
                        'system' => "Sistema",
                        'agent'  => "Asesor",
                        'customerAdmin'    => "Cliente Admin",
                        'customerUser'    => "Cliente Asesor",
                        'externalCustomer'    => "Cliente Externo",
                        'participant'    => "Participante",
                        'provider'    => "Proveedor",
                        'integration'    => "Integración",
                        'apiAdmin'    => "Api Admin",
                    ],
                    'span'    => 'left'
                ],
                'wg_provider' => [
                    'label'   => 'rainlab.user::lang.user.provider',
                    'type'    => 'dropdown',
                    'tab'     => 'rainlab.user::lang.user.details',
                    'placeholder'     => '-- select provider --',
                    'span'    => 'right'
                ],
                'company' => [
                    'label'   => 'rainlab.user::lang.user.company',
                    'tab'     => 'rainlab.user::lang.user.details',
                    'span'    => 'left'
                ],
            ]);
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'AdeN\Api\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'aden.api.some_permission' => [
                'tab' => 'api',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'api' => [
                'label'       => 'api',
                'url'         => Backend::url('aden/api/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['aden.api.*'],
                'order'       => 500,
            ],
        ];
    }

    public function register()
    {
        $this->registerConsoleCommand('aden.purgefiles', PurgeExportedZipFilesCommand::class);
    }
}
