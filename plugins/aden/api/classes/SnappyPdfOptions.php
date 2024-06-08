<?php

namespace AdeN\Api\Classes;

class SnappyPdfOptions
{
    public $pager;
    public $orientation;
    public $warnings;
    public $enableJavascript;
    public $javascriptDelay;
    public $enableSmartShrinking;
    public $noStopSlowScripts;

    public $marginTop;
    public $marginBottom;
    public $marginLeft;
    public $marginRight;

    private $options;

    function __construct($pager = 'A4', $orientation = 'portrait', $warnings = false)
    {
        $this->pager = $pager;
        $this->orientation = $orientation;
        $this->warnings = $warnings;
        $this->javascriptDelay = 0;
        $this->enableJavascript = false;
        $this->enableSmartShrinking = false;
        $this->noStopSlowScripts = false;
        $this->marginTop = 0;
        $this->marginBottom = 0;
        $this->marginLeft = 0;
        $this->marginRight = 0;
        $this->options = [];
    }

    public function setEnableJavascript($value)
    {
        $this->enableJavascript = $value;
        return $this;
    }

    public function setJavascriptDelay($value)
    {
        $this->javascriptDelay = $value;
        return $this;
    }

    public function setEnableSmartShrinking($value)
    {
        $this->enableSmartShrinking = $value;
        return $this;
    }

    public function setNoStopSlowScripts($value)
    {
        $this->noStopSlowScripts = $value;
        return $this;
    }

    public function setMarginTop($value)
    {
        $this->marginTop = $value;
        return $this;
    }

    public function setMarginBottom($value)
    {
        $this->marginBottom = $value;
        return $this;
    }

    public function setMarginLeft($value)
    {
        $this->marginLeft = $value;
        return $this;
    }

    public function setMarginRight($value)
    {
        $this->marginRight = $value;
        return $this;
    }


    public function setOption($key, $value)
    {
        $this->options = array_merge($this->options, [$key => $value]);
        return $this;
    }

    public function toArray()
    {
        return array_merge([
            'enable-javascript' => $this->enableJavascript,
            'javascript-delay' => $this->javascriptDelay,
            'enable-smart-shrinking' => $this->enableSmartShrinking,
            'no-stop-slow-scripts' => $this->noStopSlowScripts,
            'margin-top' => $this->marginTop,
            'margin-bottom' => $this->marginBottom,
            'margin-left' => $this->marginLeft,
            'margin-right' => $this->marginRight,
        ], $this->options);
    }
}
