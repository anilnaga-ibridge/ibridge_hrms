<?php

use App\Classes\Common;
use App\Models\Company;
use App\Models\Lang;
use App\Models\Translation;
use App\SuperAdmin\Models\GlobalCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('{path}', function () {
    if (file_exists(storage_path('installed'))) {

        $appName = "HrmiflySaas";
        $appVersion = File::get(public_path() . '/superadmin_version.txt');
        $modulesData = Common::moduleInformations();
        $themeMode = session()->has('theme_mode') ? session('theme_mode') : 'light';
        $company = GlobalCompany::first();
        $appVersion = File::get('superadmin_version.txt');
        $appVersion = preg_replace("/\r|\n/", "", $appVersion);
        $globalCompanyLang = DB::table('companies')->select('lang_id')->where('is_global', 1)->first();
        $lang = $globalCompanyLang && $globalCompanyLang->lang_id && $globalCompanyLang->lang_id != null ? Lang::find($globalCompanyLang->lang_id) : Lang::first();
        $loadingLangMessageLang = Translation::where('key', 'loading_app_message')
            ->where('group', 'messages')
            ->where('lang_id', $lang->id)
            ->first();
        // Logo
        if (app_type() == 'saas') {
            $company = Company::withoutGlobalScope('company')
                ->where('is_global', 1)
                ->first();
        } else {
            $company = Company::first();
        }

        $data = [
            'appName' => $appName,
            'appVersion' => preg_replace("/\r|\n/", "", $appVersion),
            'installedModules' => $modulesData['installed_modules'],
            'enabledModules' => $modulesData['enabled_modules'],
            'themeMode' => $themeMode,
            'company' => $company,
            'appVersion' => $appVersion,
            'appEnv' => env('APP_ENV'),
            'appType' => 'saas',
            'loadingLangMessageLang' => $loadingLangMessageLang ? $loadingLangMessageLang->value : '',
            'defaultLangKey' => $lang->key,
            'loadingImage' => $company->light_logo_url,
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
