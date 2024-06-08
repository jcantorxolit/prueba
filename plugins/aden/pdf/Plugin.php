<?php namespace AdeN\PDF;

use System\Classes\PluginBase;

/**
 * PDF Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'PDF',
            'description' => 'No description provided yet...',
            'author'      => 'AdeN',
            'icon'        => 'icon-leaf'
        ];
    }

}
