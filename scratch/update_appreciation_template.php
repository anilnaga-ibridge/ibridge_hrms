<?php

use Illuminate\Support\Facades\DB;
use App\Models\Company;

$companies = DB::table('companies')->get();

$newTemplate = '<style>
@page {
    size: A4-L;
    margin: 0;
}
.award-wrap {
    width: 297mm;
    height: 210mm;
    margin: 0 auto;
    background-color: #ffffff;
    position: relative;
    box-sizing: border-box;
    overflow: hidden;
    max-width: none !important;
    max-height: none !important;
}
.award-inner {
    height: 100%;
    box-sizing: border-box;
    padding: 18mm 20mm 15mm 82mm;
    position: relative;
}
.award-title {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 34pt;
    color: #0b1a30;
    letter-spacing: 6px;
    text-transform: uppercase;
    font-weight: bold;
    text-align: center;
    margin: 0;
    line-height: 1.15;
}
</style>

<div class="award-wrap">
  <!-- Gold Waves Vector graphic on the left -->
  <svg class="gold-waves" width="272" height="794" viewBox="0 0 272 794" preserveAspectRatio="none" style="position: absolute; top: 0; left: 0; bottom: 0; z-index: 1;">
    <defs>
      <linearGradient id="seal-grad-wave" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#f5e1b8" />
        <stop offset="30%" stop-color="#d4af37" />
        <stop offset="70%" stop-color="#b8972f" />
        <stop offset="100%" stop-color="#f5e1b8" />
      </linearGradient>
      <linearGradient id="gold-grad-light" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#faf2e3" stop-opacity="0.8" />
        <stop offset="50%" stop-color="#ebd3a2" stop-opacity="0.4" />
        <stop offset="100%" stop-color="#faf2e3" stop-opacity="0.1" />
      </linearGradient>
    </defs>
    <path d="M 0,0 C 90,80 140,250 110,450 C 80,650 35,730 0,794 Z" fill="url(#gold-grad-light)" />
    <path d="M 0,40 C 70,120 110,250 85,450 C 60,650 25,730 0,780 L 0,794 L 8,794 C 38,730 75,650 100,450 C 125,250 85,120 15,0 L 0,0 Z" fill="url(#seal-grad-wave)" />
    <path d="M 0,100 C 80,180 95,300 75,480 C 55,660 20,740 0,790" fill="none" stroke="#d4af37" stroke-width="2" opacity="0.9" />
    <path d="M 0,70 C 60,150 75,270 58,450 C 41,630 15,720 0,770" fill="none" stroke="#e6ca70" stroke-width="1" opacity="0.7" />
  </svg>

  <div class="award-inner">
    <!-- Top Section: Company Logo and Header -->
    <div style="text-align: center; margin-bottom: 12px; height: 30px; min-height: 30px;">
      ##COMPANY_LOGO##
    </div>
    
    <div class="award-title">Certificate</div>
    
    <!-- Subtitle Line Divider -->
    <table style="width: 100%; border: none; margin: 10px 0 15px 0; border-collapse: collapse;">
      <tr>
        <td style="border: none; width: 22%; padding: 0; vertical-align: middle;"><div style="height: 1px; background-color: #d4af37;"></div></td>
        <td style="border: none; width: 56%; text-align: center; font-family: \'Arial\', sans-serif; font-size: 10pt; color: #7f6831; letter-spacing: 5px; font-weight: bold; text-transform: uppercase; padding: 0 10px; vertical-align: middle; white-space: nowrap;">of appreciation</td>
        <td style="border: none; width: 22%; padding: 0; vertical-align: middle;"><div style="height: 1px; background-color: #d4af37;"></div></td>
      </tr>
    </table>

    <!-- Recipient Profile Image -->
    <div style="text-align: center; margin: 5px auto 10px auto; height: 75px;">
        <img src="##EMPLOYEE_PROFILE_IMAGE##" style="width: 75px; height: 75px; border-radius: 50%; object-fit: cover; border: 2.5px solid #d4af37; display: inline-block;" />
    </div>

    <!-- Body Section: Presentation and Recipient -->
    <div style="font-family: \'Arial\', sans-serif; font-size: 10pt; color: #888888; letter-spacing: 2px; text-transform: uppercase; text-align: center; margin-bottom: 10px;">This certificate is proudly presented to</div>
    
    <div style="font-family: \'Brush Script MT\', \'Great Vibes\', \'Georgia\', serif; font-size: 42pt; font-style: italic; color: #132238; margin: 15px auto 10px auto; text-align: center;">##EMPLOYEE_NAME##</div>
    <div style="width: 70%; height: 1px; background-color: #dddddd; margin: 0 auto 15px auto;"></div>
    
    <div style="font-family: \'Georgia\', serif; font-size: 11pt; color: #888888; text-transform: uppercase; letter-spacing: 1.5px; text-align: center; margin-bottom: 8px; font-style: italic;">in recognition of their outstanding achievement as</div>
    <div style="font-family: \'Georgia\', serif; font-size: 16pt; color: #c39d43; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; text-align: center; margin-bottom: 5px;">##APPRECIATION_AWARD_NAME##</div>
    <div style="font-family: \'Georgia\', serif; font-size: 13pt; color: #132238; font-weight: bold; text-align: center; margin-bottom: 20px;">##APPRECIATION_PRICE_AMOUNT##</div>
    
    <div style="font-family: \'Georgia\', serif; font-size: 12.5pt; color: #666666; line-height: 1.6; max-width: 580px; margin: 0 auto 25px auto; text-align: center; font-style: italic;">
      For outstanding performance, dedication, and exemplary hard work that contributes greatly to the success of our organization.
    </div>

    <!-- Bottom Section: Date, Gold Seal, Signature -->
    <table style="width: 100%; border: none; margin-top: 20px; border-collapse: collapse;">
      <tr>
        <td style="border: none; width: 40%; text-align: center; vertical-align: bottom; padding-bottom: 10px;">
          <div style="font-family: \'Arial\', sans-serif; font-size: 9.5pt; color: #888888; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 10px;">DATE</div>
          <div style="font-family: \'Georgia\', serif; font-size: 13pt; color: #132238; font-weight: bold; border-bottom: 1px solid #cccccc; width: 130px; margin: 0 auto; padding-bottom: 5px;">##APPRECIATION_GIVEN_DATE##</div>
        </td>
        <td style="border: none; width: 20%; text-align: center; vertical-align: middle;">
          <!-- Realistic vector gold seal stamp -->
          <svg width="70" height="70" viewBox="0 0 100 100" style="display: block; margin: 0 auto;">
            <defs>
              <linearGradient id="seal-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#ffe293" />
                <stop offset="50%" stop-color="#d4af37" />
                <stop offset="100%" stop-color="#a37c15" />
              </linearGradient>
            </defs>
            <path d="M 50 5 L 53 10 L 58 8 L 60 13 L 65 11 L 66 16 L 71 14 L 72 19 L 77 17 L 77 22 L 82 20 L 81 25 L 86 24 L 84 29 L 89 28 L 86 33 L 91 32 L 88 37 L 92 37 L 89 42 L 93 42 L 89 47 L 93 48 L 89 53 L 92 53 L 88 58 L 91 58 L 86 63 L 89 64 L 84 68 L 86 70 L 81 74 L 82 76 L 77 79 L 77 82 L 72 84 L 72 87 L 66 89 L 66 92 L 60 93 L 59 96 L 53 96 L 52 99 L 47 98 L 45 100 L 40 98 L 38 100 L 33 97 L 31 99 L 26 95 L 24 97 L 19 93 L 18 95 L 13 90 L 12 92 L 8 86 L 7 88 L 3 82 L 3 83 L 0 77 L 1 75 L 0 69 L 2 67 L 0 61 L 2 59 L 0 53 L 3 52 L 0 46 L 4 45 L 1 39 L 5 39 L 2 33 L 6 33 L 4 27 L 8 28 L 6 22 L 11 23 L 9 17 L 14 18 L 13 12 L 18 14 L 17 8 L 22 10 L 22 5 L 27 7 L 29 2 L 34 5 L 36 0 L 41 3 L 43 0 Z" fill="url(#seal-grad)" />
            <circle cx="50" cy="50" r="38" fill="none" stroke="#ffffff" stroke-width="1.5" opacity="0.8" />
            <circle cx="50" cy="50" r="35" fill="none" stroke="#a37c15" stroke-width="1" opacity="0.5" />
            <circle cx="50" cy="50" r="32" fill="none" stroke="#ffffff" stroke-width="1" stroke-dasharray="2,2" opacity="0.9" />
            <polygon points="50,33 54,43 64,43 56,49 59,59 50,53 41,59 44,49 36,43 46,43" fill="#ffffff" opacity="0.9" />
            <path id="arch-path" d="M 22 50 A 28 28 0 0 1 78 50" fill="none" stroke="none" />
            <text font-family="Arial" font-size="5.5" fill="#ffffff" font-weight="bold" letter-spacing="1">
              <textPath href="#arch-path" startOffset="50%" text-anchor="middle">EXCELLENCE</textPath>
            </text>
          </svg>
        </td>
        <td style="border: none; width: 40%; text-align: center; vertical-align: bottom; padding-bottom: 10px;">
          <div style="font-family: \'Arial\', sans-serif; font-size: 9.5pt; color: #888888; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 10px;">SIGNATURE</div>
          <div style="font-family: \'Georgia\', serif; font-size: 13pt; color: #132238; font-weight: bold; border-bottom: 1px solid #cccccc; width: 160px; margin: 0 auto; padding-bottom: 5px;">##SIGNATORY##</div>
          <div style="font-family: \'Arial\', sans-serif; font-size: 9pt; color: #999999; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px;">##SIGNATORY_DESIGNATION##</div>
        </td>
      </tr>
    </table>
  </div>
</div>';

foreach ($companies as $company) {
    // 1. Update the parent template first
    DB::table('letterhead_templates')
        ->where('company_id', $company->id)
        ->where('title', 'Award Certificate')
        ->update([
            'description' => $newTemplate,
        ]);

    // Find all appreciations for this company
    $appreciations = DB::table('appreciations')
        ->where('company_id', $company->id)
        ->get();

    foreach ($appreciations as $appreciation) {
        $awardTemplate = DB::table('letterhead_templates')
            ->where('company_id', $company->id)
            ->where('title', 'Award Certificate')
            ->first();

        if (!$awardTemplate) {
            continue;
        }

        // Update appreciation to use the Award Certificate template
        DB::table('appreciations')
            ->where('id', $appreciation->id)
            ->update([
                'letterhead_template_id' => $awardTemplate->id,
            ]);

        if ($appreciation->generates_id) {
            $generate = DB::table('generates')
                ->where('id', $appreciation->generates_id)
                ->first();

            if ($generate) {
                // Get the details
                $employee = DB::table('users')->where('id', $appreciation->user_id)->first();
                $award = DB::table('awards')->where('id', $appreciation->award_id)->first();
                $creator = DB::table('users')->where('id', $appreciation->created_by)->first();

                $designationName = '';
                if ($creator && $creator->designation_id) {
                    $designation = DB::table('designations')->where('id', $creator->designation_id)->first();
                    $designationName = $designation ? $designation->name : '';
                }

                $employeeName = $employee ? $employee->name : '';
                $awardName = $award ? $award->name : '';
                
                $appreciationDate = $appreciation->date;
                if ($appreciationDate) {
                    $appreciationDate = \Carbon\Carbon::parse($appreciationDate)->format('Y-m-d');
                } else {
                    $appreciationDate = '';
                }

                $signatoryName = $creator ? $creator->name : '';
                $signatoryDesignation = $designationName;
                
                $companyObj = Company::find($company->id);
                $logoUrl = $companyObj ? $companyObj->light_logo_url : '';
                $logoHtml = $logoUrl ? '<img src="' . $logoUrl . '" style="max-height: 30px; max-width: 130px; display: block; margin: 0 auto; object-fit: contain;" />' : '';

                $priceAmount = $appreciation->price_amount ? \App\Classes\Common::formatAmountCurrency($companyObj?->currency, $appreciation->price_amount) : '';
                $profileImageUrl = '';
                if ($appreciation->profile_image) {
                    $profileImageUrl = \App\Classes\Common::getFileUrl(\App\Classes\Common::getFolderPath('appreciationImagePath'), $appreciation->profile_image);
                } else if ($employee && $employee->profile_image) {
                    $profileImageUrl = \App\Classes\Common::getFileUrl(\App\Classes\Common::getFolderPath('userImagePath'), $employee->profile_image);
                } else {
                    $profileImageUrl = asset('images/user.png');
                }

                // Replace placeholders in the Award Certificate template description
                $templateDesc = $newTemplate;
                $newDescription = str_replace('##COMPANY_NAME##', $company->name, $templateDesc);
                $newDescription = str_replace('##COMPANY_LOGO##', $logoHtml, $newDescription);
                $newDescription = str_replace('##EMPLOYEE_NAME##', $employeeName, $newDescription);
                $newDescription = str_replace('##EMPLOYEE_PROFILE_IMAGE##', $profileImageUrl, $newDescription);
                $newDescription = str_replace('##APPRECIATION_AWARD_NAME##', $awardName, $newDescription);
                $newDescription = str_replace('##APPRECIATION_PRICE_AMOUNT##', $priceAmount, $newDescription);
                $newDescription = str_replace('##APPRECIATION_GIVEN_DATE##', $appreciationDate, $newDescription);
                $newDescription = str_replace('##SIGNATORY##', $signatoryName, $newDescription);
                $newDescription = str_replace('##SIGNATORY_DESIGNATION##', $signatoryDesignation, $newDescription);

                // Update the generates record
                DB::table('generates')
                    ->where('id', $generate->id)
                    ->update([
                        'letterhead_template_id' => $awardTemplate->id,
                        'description' => $newDescription,
                    ]);

                echo "Updated appreciation ID: {$appreciation->id} for employee: {$employeeName}\n";
            }
        }
    }
}
