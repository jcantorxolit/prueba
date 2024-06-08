<?php namespace wgroup;

use Backend\Classes\WidgetManager;
use BackendAuth;
use BackendMenu;
use Cms\Classes\ComponentManager;
use October\Rain\Support\ModuleServiceProvider;
use System\Classes\SettingsManager;

class ServiceProvider extends ModuleServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register('wgroup');

        /*
         * Register navigation
         */
        BackendMenu::registerCallback(function($manager) {
            $manager->registerMenuItems('wgroup.api', [
                
            ]);
        });

        /*
         * Register permissions
         */
        BackendAuth::registerCallback(function($manager) {
            $manager->registerPermissions('wgroup.api', [
               
            ]);
        });

        /*
         * Register widgets
         */
        WidgetManager::instance()->registerFormWidgets(function($manager){
            
        });

        /*
         * Register settings
         */
        SettingsManager::instance()->registerCallback(function($manager){
            $manager->registerSettingItems('wgroup.api', [
               
            ]);
        });

        /*
         * Register components
         */
        ComponentManager::instance()->registerComponents(function($manager){
            //$manager->registerComponent('Api\Classes\ServiceApi', 'serviceApi');
        });
    }

    /**
     * Bootstrap the module events.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot('wgroup');
    }

}
