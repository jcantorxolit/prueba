<?php namespace Tcl\Utils\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use Tcl\Utils\Models\Currency as LocaleModel;

/**
 * Locales Back-end Controller
 */
class Providers extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Tcl.Utils', 'providers');

        $this->addJs('/plugins/tcl/utils/assets/js/providers.js');
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if (!$record->is_enabled)
            return 'safe disabled';
    }

    public function onCreateForm()
    {
        $this->asExtension('FormController')->create();
        return $this->makePartial('create_form');
    }

    public function onCreate()
    {
        LocaleModel::clearCache();
        $this->asExtension('FormController')->create_onSave();
        return $this->listRefresh();
    }

    public function onUpdateForm()
    {
        $this->asExtension('FormController')->update(post('record_id'));
        $this->vars['recordId'] = post('record_id');
        return $this->makePartial('update_form');
    }

    public function onUpdate()
    {
        LocaleModel::clearCache();
        $this->asExtension('FormController')->update_onSave(post('record_id'));
        return $this->listRefresh();
    }

    public function onDelete()
    {
        LocaleModel::clearCache();
        $this->asExtension('FormController')->update_onDelete(post('record_id'));
        return $this->listRefresh();
    }
}
