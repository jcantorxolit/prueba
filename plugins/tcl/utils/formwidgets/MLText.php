<?php namespace Tcl\Utils\FormWidgets;

use Tcl\Utils\Models\Currency;

/**
 * ML Text
 * Renders a multi-lingual text field.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLText extends MLControl
{

    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'mltext';

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->isAvailable = Currency::isAvailable();

        $this->prepareVars();

        if ($this->isAvailable)
            return $this->makePartial('mltext');
        else
            return parent::render();
    }

}