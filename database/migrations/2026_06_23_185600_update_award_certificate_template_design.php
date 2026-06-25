<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companies = DB::table('companies')->get();

        foreach ($companies as $company) {
            // Update Award Certificate Template
            DB::table('letterhead_templates')
                ->where('company_id', $company->id)
                ->where('title', 'Award Certificate')
                ->update([
                    'description' => $this->getAwardTemplateDescription(),
                ]);
        }

        // Now, update any existing Generate descriptions based on 'Award Certificate'
        $awardTemplateIds = DB::table('letterhead_templates')
            ->where('title', 'Award Certificate')
            ->pluck('id')
            ->toArray();

        if (!empty($awardTemplateIds)) {
            $generates = DB::table('generates')
                ->whereIn('letterhead_template_id', $awardTemplateIds)
                ->get();

            foreach ($generates as $generate) {
                // Find associated appreciation
                $appreciation = DB::table('appreciations')
                    ->where('generates_id', $generate->id)
                    ->first();

                if ($appreciation) {
                    $employee = DB::table('users')->where('id', $appreciation->user_id)->first();
                    $award = DB::table('awards')->where('id', $appreciation->award_id)->first();
                    $creator = DB::table('users')->where('id', $appreciation->created_by)->first();
                    
                    // Designations are on designations table, let's find creator's designation
                    $designationName = '';
                    if ($creator && $creator->designation_id) {
                        $designation = DB::table('designations')->where('id', $creator->designation_id)->first();
                        $designationName = $designation ? $designation->name : '';
                    }

                    $company = DB::table('companies')->where('id', $generate->company_id)->first();

                    $employeeName = e($employee ? $employee->name : '');
                    $awardName = e($award ? $award->name : '');
                    
                    // Format date
                    $appreciationDate = $appreciation->date;
                    if ($appreciationDate) {
                        $appreciationDate = \Carbon\Carbon::parse($appreciationDate)->format('Y-m-d');
                    } else {
                        $appreciationDate = '';
                    }

                    $signatoryName = e($creator ? $creator->name : '');
                    $signatoryDesignation = e($designationName);
                    $companyName = e($company ? $company->name : '');
                    
                    $logoUrl = e($company ? $company->light_logo_url : '');
                    $logoHtml = $logoUrl ? '<img src="' . $logoUrl . '" style="max-height: 20px; max-width: 100px; display: block; margin: 0 auto; object-fit: contain;" />' : '';

                    // Resolve template
                    $newDescription = $this->getAwardTemplateDescription();
                    $newDescription = str_replace('##COMPANY_NAME##', $companyName, $newDescription);
                    $newDescription = str_replace('##COMPANY_LOGO##', $logoHtml, $newDescription);
                    $newDescription = str_replace('##EMPLOYEE_NAME##', $employeeName, $newDescription);
                    $newDescription = str_replace('##APPRECIATION_AWARD_NAME##', $awardName, $newDescription);
                    $newDescription = str_replace('##APPRECIATION_GIVEN_DATE##', $appreciationDate, $newDescription);
                    $newDescription = str_replace('##SIGNATORY##', $signatoryName, $newDescription);
                    $newDescription = str_replace('##SIGNATORY_DESIGNATION##', $signatoryDesignation, $newDescription);

                    DB::table('generates')
                        ->where('id', $generate->id)
                        ->update(['description' => $newDescription]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }

    private function getAwardTemplateDescription(): string
    {
        return '<style>
@page {
    size: A4-L;
    margin: 0;
}
.award-cert-wrapper {
    width: 148mm;
    height: 105mm;
    margin: 0 auto;
    padding: 0;
    box-sizing: border-box;
    background: #ffffff;
    position: relative;
}
.award-cert-border {
    position: absolute;
    top: 4mm;
    left: 4mm;
    right: 4mm;
    bottom: 4mm;
    border: 1px solid #c0a060;
    box-sizing: border-box;
}
.award-cert-inner {
    margin: 6mm;
    height: 85mm;
    position: relative;
    box-sizing: border-box;
    text-align: center;
}
.award-header-bar {
    width: 100%;
    height: 1.5px;
    background: linear-gradient(to right, transparent, #c0a060, transparent);
    margin-bottom: 4mm;
}
.award-company-name {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 6px;
    color: #888888;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 5mm;
}
.award-cert-title {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 14px;
    color: #2c2c2c;
    letter-spacing: 4px;
    text-transform: uppercase;
    font-weight: bold;
    margin: 0 0 1mm 0;
    line-height: 1.2;
}
.award-cert-subtitle {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 7px;
    color: #999999;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin: 0 0 5mm 0;
}
.award-divider {
    width: 35mm;
    height: 1px;
    background: #c0a060;
    margin: 0 auto 4mm auto;
}
.award-presented {
    font-family: "Arial", "Helvetica", sans-serif;
    font-size: 6px;
    color: #888888;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin: 0 0 2mm 0;
}
.award-recipient {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 17px;
    color: #1a1a1a;
    margin: 0 auto 3mm auto;
    padding-bottom: 1.5mm;
    border-bottom: 1px solid #e0d5c0;
    display: inline-block;
    width: 70%;
    line-height: 1.15;
}
.award-description {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 8px;
    color: #555555;
    line-height: 12px;
    max-width: 280px;
    margin: 0 auto 3mm auto;
    font-style: italic;
}
.award-name {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 9px;
    color: #c0a060;
    font-weight: bold;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin: 0 auto 4mm auto;
}
.award-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    border-top: 1px solid #e8e0d0;
    padding-top: 2mm;
}
.award-footer-table {
    width: 100%;
    border: none;
    border-collapse: collapse;
}
.award-footer-td {
    border: none;
    width: 33%;
    vertical-align: bottom;
    text-align: center;
}
.award-footer-label {
    font-family: "Arial", "Helvetica", sans-serif;
    font-size: 6px;
    color: #888888;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: bold;
}
.award-footer-value {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 8px;
    color: #1a1a1a;
    font-weight: bold;
}
.award-footer-line {
    border-bottom: 1px solid #cccccc;
    width: 75px;
    margin: 0 auto 1mm auto;
}
.award-signature-name {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 8px;
    color: #1a1a1a;
    font-weight: bold;
}
.award-signature-title {
    font-family: "Arial", "Helvetica", sans-serif;
    font-size: 6px;
    color: #888888;
    text-transform: uppercase;
    letter-spacing: 1px;
}
</style>

<div class="award-cert-wrapper">
  <div class="award-cert-border"></div>
  <div class="award-cert-inner">
    <div class="award-header-bar"></div>

    <div class="award-company-name">##COMPANY_NAME##</div>

    <div class="award-cert-title">Certificate</div>
    <div class="award-cert-subtitle">of Authenticity</div>

    <div class="award-divider"></div>

    <div class="award-presented">This certificate is presented to</div>

    <div class="award-recipient">##EMPLOYEE_NAME##</div>

    <div class="award-description">
      For outstanding performance, dedication, and exemplary hard work that contributes greatly to the success of our organization.
    </div>

    <div class="award-name">##APPRECIATION_AWARD_NAME##</div>

    <div class="award-footer">
      <table class="award-footer-table">
        <tr>
          <td class="award-footer-td">
            <div class="award-footer-label">Date</div>
            <div class="award-footer-line"></div>
            <div class="award-footer-value">##APPRECIATION_GIVEN_DATE##</div>
          </td>
          <td class="award-footer-td">
            <div style="max-height: 20px; display: flex; align-items: center; justify-content: center;">##COMPANY_LOGO##</div>
          </td>
          <td class="award-footer-td">
            <div class="award-footer-line"></div>
            <div class="award-signature-name">##SIGNATORY##</div>
            <div class="award-signature-title">##SIGNATORY_DESIGNATION##</div>
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>';
    }
};
