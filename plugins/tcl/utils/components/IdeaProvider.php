<?php

namespace Tcl\Utils\Components;

use Cms\Classes\ComponentBase;
use Tcl\Utils\Models\Provider as ProviderModel;
use Request;
use App;
use DB;

class IdeaProvider extends ComponentBase {

    public function componentDetails() {
        return [
            'name' => 'Idea Provider',
            'description' => 'Managment of providers for ideas importer'
        ];
    }

    /**
     * @return array
     * @todo Change start to parentNode to match my naming
     */
    public function defineProperties() {
        return [
            'listItemClasses' => [
                'description' => 'Classes to add to the li tag',
                'title' => 'List Item Classes',
                'default' => 'item',
                'type' => 'string'
            ],
            'primaryClasses' => [
                'description' => 'Classes to add to the primary ul tag',
                'title' => 'Primary Classes',
                'default' => 'nav nav-pills',
                'type' => 'string'
            ]
        ];
    }

    /**
     * Returns the list of menu items I can select
     * @return array
     */
    public function getStartOptions() {
        $ProviderModel = new ProviderModel();
        return $ProviderModel->getSelectList();
    }

    /**
     * Returns the list of menu items, plus an empty default option
     *
     * @return array
     */
    public function getActiveNodeOptions() {
        $options = $this->getStartOptions();
        array_unshift($options, 'default');

        return $options;
    }

    /**
     * Build all my parameters for the view
     * @todo Pull as much as possible into the model, including the column names
     */
    public function onRender() {
        // Set the parentNode for the component output
        // recupero todos los proveedores activos y que permitan importacion
        $providers = (new ProviderModel())->where("active", 1)->where("import", 1)->orderBy("name")->get();

        $this->page['providers'] = $providers;
        $this->page['providers_count'] = count($providers);
        $this->page['primaryClasses'] = $this->property('primaryClasses');
        $this->page['listItemClasses'] = $this->property('listItemClasses');
    }

    /**
     * Gets the id from the passed property
     *  Due to the component inspector re-ordering the array on keys, and me using the key as the menu model id,
     *  I've been forced to add a string to the key. This method removes it and returns the raw id.
     *
     * @param $value
     *
     * @return bool|string
     */
    protected function getIdFromProperty($value) {
        if (!strlen($value) > 3) {
            return false;
        }
        return substr($value, 3);
    }

}
