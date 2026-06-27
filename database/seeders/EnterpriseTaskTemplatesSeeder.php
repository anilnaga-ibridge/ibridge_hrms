<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Services\EnterpriseTasks\TemplateService;
use Illuminate\Database\Seeder;

class EnterpriseTaskTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = Company::all();
        $templateService = app(TemplateService::class);

        foreach ($companies as $company) {
            $user = User::where('company_id', $company->id)->first();
            if ($user) {
                $templateService->createSystemTemplates($company->id, $user->id);
            }
        }
    }
}
