<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $companies = Company::with(['currency'])->get();

        foreach ($companies as $company) {
            // Update Award Certificate template
            DB::table('letterhead_templates')
                ->where('company_id', $company->id)
                ->where('title', 'Award Certificate')
                ->update([
                    'description' => $this->getAwardTemplateDescription(),
                ]);

            // Update Birthday Certificate template
            DB::table('letterhead_templates')
                ->where('company_id', $company->id)
                ->where('title', 'Birthday Certificate')
                ->update([
                    'description' => $this->getBirthdayTemplateDescription(),
                ]);
        }

        // Update existing Generate descriptions for Award Certificate
        $awardTemplateIds = DB::table('letterhead_templates')
            ->where('title', 'Award Certificate')
            ->pluck('id')
            ->toArray();

        if (!empty($awardTemplateIds)) {
            $generates = DB::table('generates')
                ->whereIn('letterhead_template_id', $awardTemplateIds)
                ->get();

            foreach ($generates as $generate) {
                $appreciation = DB::table('appreciations')
                    ->where('generates_id', $generate->id)
                    ->first();

                if ($appreciation) {
                    $employee = DB::table('users')->where('id', $appreciation->user_id)->first();
                    $award = DB::table('awards')->where('id', $appreciation->award_id)->first();
                    $creator = DB::table('users')->where('id', $appreciation->created_by)->first();
                    $companyObj = Company::find($generate->company_id);

                    $designationName = '';
                    if ($creator && $creator->designation_id) {
                        $designation = DB::table('designations')->where('id', $creator->designation_id)->first();
                        $designationName = $designation ? $designation->name : '';
                    }

                    $employeeName = $employee ? htmlspecialchars($employee->name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                    $awardName    = $award    ? htmlspecialchars($award->name,     ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                    $appreciationDate = $appreciation->date;
                    if ($appreciationDate) {
                        $appreciationDate = htmlspecialchars(
                            \Carbon\Carbon::parse($appreciationDate)->format('Y-m-d'),
                            ENT_QUOTES | ENT_HTML5, 'UTF-8'
                        );
                    } else {
                        $appreciationDate = '';
                    }

                    $signatoryName        = $creator ? htmlspecialchars($creator->name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                    $signatoryDesignation = htmlspecialchars($designationName,          ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $companyName          = $companyObj ? htmlspecialchars($companyObj->name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

                    $logoUrl  = $companyObj ? $companyObj->light_logo_url : '';
                    // Escape the URL into the attribute value to prevent attribute-injection.
                    $logoHtml = $logoUrl
                        ? '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                          . '" style="max-height: 20px; max-width: 100px; display: block; margin: 0 auto; object-fit: contain;" />'
                        : '';

                    $newDescription = $this->getAwardTemplateDescription();
                    $newDescription = str_replace('##COMPANY_NAME##',           $companyName,          $newDescription);
                    $newDescription = str_replace('##COMPANY_LOGO##',           $logoHtml,             $newDescription);
                    $newDescription = str_replace('##EMPLOYEE_NAME##',          $employeeName,         $newDescription);
                    $newDescription = str_replace('##APPRECIATION_AWARD_NAME##',$awardName,            $newDescription);
                    $newDescription = str_replace('##APPRECIATION_GIVEN_DATE##',$appreciationDate,     $newDescription);
                    $newDescription = str_replace('##SIGNATORY##',              $signatoryName,        $newDescription);
                    $newDescription = str_replace('##SIGNATORY_DESIGNATION##',  $signatoryDesignation, $newDescription);

                    DB::table('generates')
                        ->where('id', $generate->id)
                        ->update(['description' => $newDescription]);
                }
            }
        }

        // Update existing Generate descriptions for Birthday Certificate
        $birthdayTemplateIds = DB::table('letterhead_templates')
            ->where('title', 'Birthday Certificate')
            ->pluck('id')
            ->toArray();

        if (!empty($birthdayTemplateIds)) {
            $generates = DB::table('generates')
                ->whereIn('letterhead_template_id', $birthdayTemplateIds)
                ->get();

            foreach ($generates as $generate) {
                $appreciation = DB::table('appreciations')
                    ->where('generates_id', $generate->id)
                    ->first();

                if ($appreciation) {
                    $employee = DB::table('users')->where('id', $appreciation->user_id)->first();
                    $companyObj = Company::find($generate->company_id);
                    $employeeName = $employee    ? htmlspecialchars($employee->name,    ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                    $companyName  = $companyObj  ? htmlspecialchars($companyObj->name,  ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

                    $newDescription = $this->getBirthdayTemplateDescription();
                    $newDescription = str_replace('##EMPLOYEE_NAME##', $employeeName, $newDescription);
                    $newDescription = str_replace('##COMPANY_NAME##',  $companyName,  $newDescription);

                    DB::table('generates')
                        ->where('id', $generate->id)
                        ->update(['description' => $newDescription]);
                }
            }
        }
    }

    public function down(): void
    {
        // This migration overwrites the `description` column of existing letterhead
        // template and generate records. The original HTML is not stored anywhere,
        // so a safe automatic rollback is not possible.
        throw new \Illuminate\Database\Migrations\IrreversibleMigrationException();
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

    private function getBirthdayTemplateDescription(): string
    {
        return '<style>
@page {
    size: A4-L;
    margin: 0;
}
.birthday-cert-wrapper {
    width: 297mm;
    height: 210mm;
    margin: 0;
    padding: 15px;
    background-color: #f5ebd5;
    position: relative;
    box-sizing: border-box;
}
.birthday-cert-inner {
    border: 2px double #795548;
    padding: 30px;
    height: 176mm;
    box-sizing: border-box;
    text-align: center;
    position: relative;
}
</style>
<div class="birthday-cert-wrapper">
  <div class="birthday-cert-inner">
    <svg width="75" height="75" viewBox="0 0 100 100" style="position: absolute; top: 10px; left: 10px;">
      <g transform="translate(0, 0)">
        <path d="M0,0 Q30,5 50,30 Q30,25 20,40 Q15,30 0,0" fill="#795548"/>
        <path d="M0,0 Q5,30 30,50 Q25,30 40,20 Q30,15 0,0" fill="#795548"/>
        <circle cx="25" cy="25" r="4" fill="#5d4037"/>
        <circle cx="35" cy="15" r="3" fill="#5d4037"/>
        <circle cx="15" cy="35" r="3" fill="#5d4037"/>
      </g>
    </svg>
    
    <svg width="75" height="75" viewBox="0 0 100 100" style="position: absolute; top: 10px; right: 10px;">
      <g transform="translate(100, 0) scale(-1, 1)">
        <path d="M0,0 Q30,5 50,30 Q30,25 20,40 Q15,30 0,0" fill="#795548"/>
        <path d="M0,0 Q5,30 30,50 Q25,30 40,20 Q30,15 0,0" fill="#795548"/>
        <circle cx="25" cy="25" r="4" fill="#5d4037"/>
        <circle cx="35" cy="15" r="3" fill="#5d4037"/>
        <circle cx="15" cy="35" r="3" fill="#5d4037"/>
      </g>
    </svg>

    <svg width="75" height="75" viewBox="0 0 100 100" style="position: absolute; bottom: 10px; right: 10px;">
      <g transform="translate(100, 100) scale(-1, -1)">
        <path d="M0,0 Q30,5 50,30 Q30,25 20,40 Q15,30 0,0" fill="#795548"/>
        <path d="M0,0 Q5,30 30,50 Q25,30 40,20 Q30,15 0,0" fill="#795548"/>
        <circle cx="25" cy="25" r="4" fill="#5d4037"/>
        <circle cx="35" cy="15" r="3" fill="#5d4037"/>
        <circle cx="15" cy="35" r="3" fill="#5d4037"/>
      </g>
    </svg>

    <svg width="100" height="100" viewBox="0 0 120 120" style="position: absolute; bottom: 5px; left: 5px;">
      <g transform="translate(15, 105) scale(1, -1)">
        <path d="M0,0 Q30,5 50,30 Q30,25 20,40 Q15,30 0,0" fill="#795548" opacity="0.6"/>
        <path d="M0,0 Q5,30 30,50 Q25,30 40,20 Q30,15 0,0" fill="#795548" opacity="0.6"/>
      </g>
      <circle cx="65" cy="65" r="35" fill="#cfb28c" stroke="#5d4037" stroke-width="4"/>
      <circle cx="65" cy="65" r="30" fill="#fffef7" stroke="#795548" stroke-width="1.5"/>
      <rect x="62" y="22" width="6" height="8" fill="#5d4037"/>
      <circle cx="65" cy="20" r="5" fill="none" stroke="#5d4037" stroke-width="2"/>
      <line x1="65" y1="65" x2="65" y2="45" stroke="#2c1e18" stroke-width="2.5" stroke-linecap="round"/>
      <line x1="65" y1="65" x2="80" y2="60" stroke="#2c1e18" stroke-width="2" stroke-linecap="round"/>
      <circle cx="65" cy="65" r="3" fill="#2c1e18"/>
      <line x1="65" y1="36" x2="65" y2="40" stroke="#2c1e18" stroke-width="1.5"/>
      <line x1="65" y1="94" x2="65" y2="90" stroke="#2c1e18" stroke-width="1.5"/>
      <line x1="36" y1="65" x2="40" y2="65" stroke="#2c1e18" stroke-width="1.5"/>
      <line x1="94" y1="65" x2="90" y2="65" stroke="#2c1e18" stroke-width="1.5"/>
      <path d="M 25 80 C 23 75, 18 77, 21 83 C 18 88, 23 90, 25 85 C 28 90, 33 88, 30 83 C 33 77, 28 75, 25 80 Z" fill="#e8dbcd" stroke="#5d4037" stroke-width="1"/>
      <circle cx="25" cy="82" r="2.5" fill="#5d4037"/>
    </svg>

    <div style="margin-top: 10px;">
      <h1 style="font-family: \'Times New Roman\', Georgia, serif; font-size: 30px; color: #5d4037; letter-spacing: 4px; margin: 0; font-weight: bold; text-transform: uppercase;">Birthday</h1>
      <h2 style="font-family: \'Times New Roman\', Georgia, serif; font-size: 22px; color: #5d4037; letter-spacing: 5px; margin: 3px 0 15px 0; font-weight: bold; text-transform: uppercase;">Certificate</h2>
      
      <p style="font-family: \'Georgia\', serif; font-style: italic; font-size: 15px; color: #795548; margin: 0 0 10px 0;">Happy Birthday,</p>
      
      <h3 style="font-family: \'Georgia\', serif; font-style: italic; font-size: 28px; color: #8d6e63; margin: 3px 0 15px 0; border-bottom: 1.5px solid #bcaaa4; display: inline-block; padding-bottom: 4px; width: 60%;">##EMPLOYEE_NAME##</h3>
      
      <p style="font-family: \'Georgia\', serif; font-size: 12px; color: #5d4037; line-height: 18px; margin: 10px 0 8px 0; padding: 0 30px;">
        Wishing you a very happy birthday and a wonderful year ahead. Your dedication, hard work, and positive attitude make a valuable contribution to our team every day.
      </p>
      
      <p style="font-family: \'Georgia\', serif; font-size: 12px; color: #5d4037; line-height: 18px; margin: 0 0 10px 0; padding: 0 30px;">
        May this special day bring you happiness, good health, and success in all your personal and professional endeavors. We appreciate your commitment and look forward to achieving many more milestones together.
      </p>
      
      <p style="font-family: \'Georgia\', serif; font-size: 12px; font-weight: bold; color: #795548; line-height: 18px; margin: 0 auto 15px auto; padding: 0 30px; font-style: italic;">
        Have an amazing birthday and a fantastic year ahead!
      </p>
      
      <table style="width: 100%; border: none; margin-top: 10px;">
        <tr>
          <td style="border: none; width: 33%;"></td>
          <td style="border: none; width: 34%; text-align: center; vertical-align: bottom;">
            <span style="font-family: \'Georgia\', serif; font-style: italic; font-size: 11px; color: #795548;">Warm Regards,</span><br/>
            <span style="font-family: \'Georgia\', serif; font-size: 13px; font-weight: bold; color: #5d4037;">##COMPANY_NAME##</span>
          </td>
          <td style="border: none; width: 33%;"></td>
        </tr>
      </table>
    </div>
  </div>
</div>';
    }
};
