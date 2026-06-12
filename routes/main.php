<?php

use App\Classes\Common;
use App\Models\Company;
use App\Models\Lang;
use App\Models\Translation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('{path}', function () {
    if (file_exists(storage_path('installed'))) {

        $appName = "iBridge HR";
        $appVersion = File::get(public_path() . '/version.txt');
        $modulesData = Common::moduleInformations();
        $themeMode = session()->has('theme_mode') ? session('theme_mode') : 'light';
        $company = Company::first();
        $appVersion = File::get('version.txt');
        $appVersion = preg_replace("/\r|\n/", "", $appVersion);
        $lang = $company && $company->lang_id && $company->lang_id != null ? Lang::find($company->lang_id) : Lang::first();
        $loadingLangMessageLang = Translation::where('key', 'loading_app_message')
            ->where('group', 'messages')
            ->where('lang_id', $lang->id)
            ->first();

        $data = [
            'appName' => $appName,
            'appVersion' => preg_replace("/\r|\n/", "", $appVersion),
            'installedModules' => $modulesData['installed_modules'],
            'enabledModules' => $modulesData['enabled_modules'],
            'themeMode' => $themeMode,
            'company' => $company,
            'appEnv' => env('APP_ENV'),
            'appType' => 'non-saas',
            'loadingImage' => $company->light_logo_url,
            'loadingLangMessageLang' => $loadingLangMessageLang ? $loadingLangMessageLang->value : '',
        ];

        if (request()->is('admin/login') || request()->path() == 'admin/login' || request()->route('path') == 'admin/login') {
            $langKey = front_lang_key();
            $selectedLang = Lang::where('key', $langKey)->first();
            $allLangs = Lang::where('enabled', 1)->get();

            $frontSettings = \App\SuperAdmin\Models\GlobalSettings::where('setting_type', 'website_settings')
                ->where('name_key', $langKey)
                ->first();
            $settings = $frontSettings->credentials;
            $settings = Common::addWebsiteImageUrl($settings, 'header_logo');
            $settings = Common::addWebsiteImageUrl($settings, 'header_sidebar_logo');
            $settings = Common::addWebsiteImageUrl($settings, 'footer_logo');
            $settings = Common::addWebsiteImageUrl($settings, 'header_background_image');
            $frontSetting = (object) $settings;

            $showFullHeader = false;
            $breadcrumbTitle = $frontSetting->register_text;
            $hideBreadcrumb = true;

            $footerPagesSetting = \App\SuperAdmin\Models\GlobalSettings::where('setting_type', 'footer_pages')
                ->where('name_key', $langKey)
                ->first();
            $footerPages = $footerPagesSetting->credentials;
            $footerPages = Common::convertToCollection($footerPages);

            $data['langKey'] = $langKey;
            $data['selectedLang'] = $selectedLang;
            $data['allLangs'] = $allLangs;
            $data['frontSetting'] = $frontSetting;
            $data['showFullHeader'] = $showFullHeader;
            $data['breadcrumbTitle'] = $breadcrumbTitle;
            $data['hideBreadcrumb'] = $hideBreadcrumb;
            $data['footerPages'] = $footerPages;
        }

        return view('welcome', $data);
    } else {
        return redirect('/install');
    }
})->where('path', '^(?!api.*$).*')->name('main');
