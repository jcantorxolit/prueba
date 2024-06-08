<?php namespace  Tcl\Utils\Components;

use Redirect;
use RainLab\Translate\Models\Locale as LocaleModel;
use RainLab\Translate\Classes\Translator;
use Cms\Classes\ComponentBase;
use Log;

class TclLocalePicker extends ComponentBase
{
    private $translator;

    public $locales;
    public $activeLocale;

    public function componentDetails()
    {
        return [
            'name'         => 'Tcl Locale Picker',
            'description'  => 'Extend Rainlab Locale Picker',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->translator = Translator::instance();
    }

    public function onRun()
    {
        $this->page['activeLocale'] = $this->activeLocale = $this->translator->getLocale();
        $this->page['locales'] = $this->locales = LocaleModel::listEnabled();
    }

    public function onSwitchTclLocale()
    {
         $path = post('path');
         
         Log::info("onSwitchLocale .... [".$path."]");
         
        if (!$locale = post('locale'))
            return;

        $this->translator->setLocale($locale);
        
        $url = $this->currentPageUrl();
         
        if ($path) {
            $url = $path;
        }
        
        return Redirect::to($path);
    }

}