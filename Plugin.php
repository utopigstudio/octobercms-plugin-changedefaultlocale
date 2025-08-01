<?php namespace Utopigs\ChangeDefaultLocale;

use System\Classes\PluginBase;
use Backend;

/**
 * ChangeDefaultLocale Plugin Information File
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
            'name' => 'utopigs.changedefaultlocale::lang.plugin.name',
            'description' => 'utopigs.changedefaultlocale::lang.plugin.description',
            'author' => 'Utopig Studio',
            'icon' => 'icon-language',
            'homepage' => 'https://github.com/utopigstudio/octobercms-plugin-change-default-locale'
        ];
    }

    public function registerSettings()
    {
        return [
            'changedefaultlocale' => [
                'label'       => 'utopigs.changedefaultlocale::lang.settings.name',
                'description' => 'utopigs.changedefaultlocale::lang.settings.description',
                'icon'        => 'icon-language',
                'url'         => Backend::url('utopigs/changedefaultlocale/changedefaultlocale'),
                'order'       => 552,
                'category'    => 'system::lang.system.categories.system',
                'permissions' => ['settings.manage_sites']
            ]
        ];
    }

}