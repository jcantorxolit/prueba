<?php

namespace Tcl\Utils\Components;

use Cms\Classes\ComponentBase;
use Redirect;
use Tcl\Utils\Classes\Converter;
use Tcl\Utils\Models\Currency as CurrencyModel;
use Log;

class CurrencyPicker extends ComponentBase {

    private $converter;
    public $currencies;
    public $activeCurrency;

    public function componentDetails() {
        return [
            'name' => 'tcl.utils::lang.currency_picker.component_name',
            'description' => 'tcl.utils::lang.currency_picker.component_description',
        ];
    }

    public function defineProperties() {
        return [];
    }

    public function init() {
        $this->converter = Converter::instance();
    }

    public function onRun() {
        $this->page['activeCurrency'] = $this->activeCurrency = $this->converter->getCurrency();
        $this->page['currencies'] = $this->currencies = CurrencyModel::listEnabled();
    }

    public function onSwitchCurrency() {
        $path = post('path');

        if (!$currency = post('currency'))
            return;

        $this->converter->setCurrency($currency);

        $url = $this->currentPageUrl();
        
        if ($path) {
            $url = $path;
        }
        Log::info("redireccionando a: [".$path."]");
        
        return Redirect::to($path);
    }

}
