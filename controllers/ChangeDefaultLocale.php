<?php namespace Utopigs\ChangeDefaultLocale\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use DB;
use Flash;
use Lang;
use Log;
use System\Classes\SettingsManager;
use System\Helpers\Cache as CacheHelper;

/**
 * ChangeDefaultLocale Back-end Controller
 */
class ChangeDefaultLocale extends Controller
{

    public $requiredPermissions = ['settings.manage_sites'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Utopigs.ChangeDefaultLocale', 'changedefaultlocale');
    }

    public function index()
    {
        $this->pageTitle = 'utopigs.changedefaultlocale::lang.changedefaultlocale.title';

        $locales = \RainLab\Translate\Classes\Locale::listEnabled();
        $defaultLocale = \RainLab\Translate\Classes\Translator::instance()->getDefaultLocale();
        $locales = array_filter($locales, function ($code) use ($defaultLocale) {
            return $code !== $defaultLocale;
        }, ARRAY_FILTER_USE_KEY);

        $this->vars['availableLocales'] = $locales;
    }

    public function onChangeDefaultLocale()
    {
        $new_default = input('newDefault');
        if (!$new_default) return;

        $old_default = \RainLab\Translate\Classes\Translator::instance()->getDefaultLocale();

        if ($old_default == $new_default) {
            Flash::warning(Lang::get('utopigs.changedefaultlocale::lang.changedefaultlocale.default_error'));
            return;
        }

        $indexes = Db::table('rainlab_translate_indexes')->get();
        $messages = Db::table('rainlab_translate_attributes')->get();

        foreach ($indexes as $message) {
            if ($message->locale != $new_default) continue;

            $model_type = $message->model_type;
            $model_id = $message->model_id;
            $field = $message->item;

            if (!class_exists($model_type)) continue;
            $element = (new $model_type)->find($model_id);
            if (!$element) continue;

            Db::table('rainlab_translate_indexes')
                ->where('model_type', $model_type)
                ->where('model_id', $model_id)
                ->where('locale', $new_default)
                ->where('item', $field)
                ->update(['locale' => $old_default, 'value' => $element->$field]);
        }

        $errors = 0;

        foreach ($messages as $message) {
            if ($message->locale != $new_default) continue;

            $model_type = $message->model_type;
            $model_id = $message->model_id;

            if (!class_exists($model_type)) continue;
            $element = (new $model_type)->find($model_id);
            if (!$element) continue;

            $new_default_texts = json_decode($message->attribute_data, true);
            $old_default_texts = [];

            foreach($new_default_texts as $field => $value) {
                $old_default_texts[$field] = $element->$field;
                if (!empty($value)) {
                    $element->$field = $value;
                }
            }

            try {
                $element->save();
                Log::info('Model ' . $model_type . ' id ' . $model_id . ' translations saved');

                Db::table('rainlab_translate_attributes')
                    ->where('model_type', $model_type)
                    ->where('model_id', $model_id)
                    ->where('locale', $new_default)
                    ->update(['locale' => $old_default, 'attribute_data' => json_encode($old_default_texts, JSON_UNESCAPED_UNICODE)]);

            } catch (\Exception $e) {
                $errors++;
                Log::info('Model ' . $model_type . ' id ' . $model_id . ' translations not saved');
                Log::error($e->getMessage());
            }
        }
        
        if ($errors) {
            Flash::warning(Lang::get('utopigs.changedefaultlocale::lang.changedefaultlocale.errors'));
            return;
        }

        // TODO: update translatable system files

        Db::table('system_site_definitions')
        ->update(['is_primary' => false]);

        Db::table('system_site_definitions')
        ->where('locale', $new_default)
        ->update(['is_primary' => true]);

        CacheHelper::clear();

        Flash::success(Lang::get('utopigs.changedefaultlocale::lang.changedefaultlocale.done'));
    }
}
