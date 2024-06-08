<?php namespace Tcl\Utils\FormWidgets;

use Tcl\Utils\Models\Currency;
use Backend\Classes\FormWidgetBase;

/**
 * Generic ML Control
 * Renders a multi-lingual control.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class MLControl extends FormWidgetBase
{
    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'mlcontrol';

    /**
     * @var string Form field column name.
     */
    public $columnName;

    /**
     * @var boolean Determines whether translation services are available
     */
    public $isAvailable;

    /**
     * @var string If translation is unavailable, fall back to this standard field.
     */
    public $fallbackType = 'text';

    /**
     * @var string Specifies a path to the views directory.
     */
    protected $parentViewPath;

    /**
     * Initialize control
     * @return void
     */
    public function init()
    {
        $this->columnName  = $this->formField->fieldName;
        $this->defaultCurrency  = Currency::getDefault();
        $this->parentViewPath = $this->guessViewPathFrom(__CLASS__, '/partials');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        return $this->makeParentPartial('fallback_field');
    }

    /**
     * Used by child classes to render in context of this view path.
     * @param string $partial The view to load.
     * @param array $params Parameter variables to pass to the view.
     * @return string The view contents.
     */
    public function makeParentPartial($partial, $params = [])
    {
        $oldViewPath = $this->viewPath;
        $this->viewPath = $this->parentViewPath;
        $result = $this->makePartial($partial, $params);
        $this->viewPath = $oldViewPath;
        return $result;
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['defaultCurrency'] = $this->defaultCurrency;
        $this->vars['currencies'] = Currency::listAvailable();
        $this->vars['field'] = $this->makeRenderFormField();
    }

    /**
     * Returns a translated value for a given currency.
     * @param  string $currency
     * @return string
     */
    public function getCurrencyValue($currency)
    {
        if ($this->model->methodExists('getTranslateAttribute'))
            return $this->model->getTranslateAttribute($this->columnName, $currency);
        else
            return $this->formField->value;
    }

    /**
     * If translation is unavailable, render the original field type (text).
     */
    protected function makeRenderFormField()
    {
        if ($this->isAvailable)
            return $this->formField;

        $field = clone $this->formField;
        $field->type = $this->fallbackType;
        return $field;
    }

    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        $this->addJs('/plugins/tcl/utils/assets/js/converter.js', 'Tcl.Utils');
        $this->addCss('/plugins/tcl/utils/assets/css/converter.css', 'Tcl.Utils');
    }

    /**
     * {@inheritDoc}
     */
    public function getSaveData($value)
    {
        $currencyData = $this->getCurrencySaveData();

        /*
         * Set the translated values to the model
         */
        if ($this->model->methodExists('setTranslateAttribute')) {
            foreach ($currencyData as $currency => $value) {
                $this->model->setTranslateAttribute($this->columnName, $value, $currency);
            }
        }

        return array_get($currencyData, $this->defaultCurrency->code, $value);
    }

    /**
     * Returns an array of translated values for this field
     * @return array
     */
    public function getCurrencySaveData()
    {
        $data = post('RLTranslate');
        if (!is_array($data))
            return [];

        $values = [];
        foreach ($data as $currency => $_data) {
            $values[$currency] = array_get($_data, $this->columnName);
        }

        return $values;
    }

}
