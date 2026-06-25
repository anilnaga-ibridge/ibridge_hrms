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
            // Update Birthday Certificate
            DB::table('letterhead_templates')
                ->where('company_id', $company->id)
                ->where('title', 'Birthday Certificate')
                ->update([
                    'description' => $this->getBirthdayTemplateDescription(),
                ]);

            // Update Award Certificate
            DB::table('letterhead_templates')
                ->where('company_id', $company->id)
                ->where('title', 'Award Certificate')
                ->update([
                    'description' => $this->getAwardTemplateDescription(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed as this refines the template descriptions
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
    padding: 20px;
    background-color: #f5ebd5;
    position: relative;
    box-sizing: border-box;
}
.birthday-cert-inner {
    border: 3px double #795548;
    padding: 40px;
    height: 164mm;
    box-sizing: border-box;
    text-align: center;
    position: relative;
}
</style>
<div class="birthday-cert-wrapper">
  <div class="birthday-cert-inner">
    <!-- Ornaments Top-Left -->
    <svg width="100" height="100" viewBox="0 0 100 100" style="position: absolute; top: 15px; left: 15px;">
      <g transform="translate(0, 0)">
        <path d="M0,0 Q30,5 50,30 Q30,25 20,40 Q15,30 0,0" fill="#795548"/>
        <path d="M0,0 Q5,30 30,50 Q25,30 40,20 Q30,15 0,0" fill="#795548"/>
        <circle cx="25" cy="25" r="4" fill="#5d4037"/>
        <circle cx="35" cy="15" r="3" fill="#5d4037"/>
        <circle cx="15" cy="35" r="3" fill="#5d4037"/>
      </g>
    </svg>
    
    <!-- Ornaments Top-Right -->
    <svg width="100" height="100" viewBox="0 0 100 100" style="position: absolute; top: 15px; right: 15px;">
      <g transform="translate(100, 0) scale(-1, 1)">
        <path d="M0,0 Q30,5 50,30 Q30,25 20,40 Q15,30 0,0" fill="#795548"/>
        <path d="M0,0 Q5,30 30,50 Q25,30 40,20 Q30,15 0,0" fill="#795548"/>
        <circle cx="25" cy="25" r="4" fill="#5d4037"/>
        <circle cx="35" cy="15" r="3" fill="#5d4037"/>
        <circle cx="15" cy="35" r="3" fill="#5d4037"/>
      </g>
    </svg>

    <!-- Ornaments Bottom-Right -->
    <svg width="100" height="100" viewBox="0 0 100 100" style="position: absolute; bottom: 15px; right: 15px;">
      <g transform="translate(100, 100) scale(-1, -1)">
        <path d="M0,0 Q30,5 50,30 Q30,25 20,40 Q15,30 0,0" fill="#795548"/>
        <path d="M0,0 Q5,30 30,50 Q25,30 40,20 Q30,15 0,0" fill="#795548"/>
        <circle cx="25" cy="25" r="4" fill="#5d4037"/>
        <circle cx="35" cy="15" r="3" fill="#5d4037"/>
        <circle cx="15" cy="35" r="3" fill="#5d4037"/>
      </g>
    </svg>

    <!-- Ornaments Bottom-Left & Pocket Watch -->
    <svg width="130" height="130" viewBox="0 0 120 120" style="position: absolute; bottom: 5px; left: 5px;">
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

    <div style="margin-top: 5px;">
      <!-- Company Logo -->
      <div style="margin-top: 10px; text-align: center; margin-bottom: 8px;">
        ##COMPANY_LOGO##
      </div>
      
      <h1 style="font-family: \'Times New Roman\', Georgia, serif; font-size: 36px; color: #5d4037; letter-spacing: 5px; margin: 0; font-weight: bold; text-transform: uppercase;">Birthday</h1>
      <h2 style="font-family: \'Times New Roman\', Georgia, serif; font-size: 26px; color: #5d4037; letter-spacing: 7px; margin: 5px 0 15px 0; font-weight: bold; text-transform: uppercase;">Certificate</h2>
      
      <p style="font-family: \'Georgia\', serif; font-style: italic; font-size: 16px; color: #795548; margin: 0 0 10px 0;">Happy Birthday,</p>
      
      <h3 style="font-family: \'Georgia\', serif; font-style: italic; font-size: 34px; color: #8d6e63; margin: 5px 0 15px 0; border-bottom: 2px solid #bcaaa4; display: inline-block; padding-bottom: 5px; width: 65%;">##EMPLOYEE_NAME##</h3>
      
      <p style="font-family: \'Georgia\', serif; font-size: 14px; color: #5d4037; line-height: 22px; margin: 10px 0 8px 0; padding: 0 40px;">
        Wishing you a very happy birthday and a wonderful year ahead. Your dedication, hard work, and positive attitude make a valuable contribution to our team every day.
      </p>
      
      <p style="font-family: \'Georgia\', serif; font-size: 14px; color: #5d4037; line-height: 22px; margin: 0 0 12px 0; padding: 0 40px;">
        May this special day bring you happiness, good health, and success in all your personal and professional endeavors. We appreciate your commitment and look forward to achieving many more milestones together.
      </p>
      
      <p style="font-family: \'Georgia\', serif; font-size: 14px; font-weight: bold; color: #795548; line-height: 22px; margin: 0 auto 15px auto; padding: 0 40px; font-style: italic;">
        Have an amazing birthday and a fantastic year ahead!
      </p>
      
      <table style="width: 100%; border: none; margin-top: 10px;">
        <tr>
          <td style="border: none; width: 33%;"></td>
          <td style="border: none; width: 34%; text-align: center; vertical-align: bottom;">
            <span style="font-family: \'Georgia\', serif; font-style: italic; font-size: 12px; color: #795548;">Warm Regards,</span><br/>
            <span style="font-family: \'Georgia\', serif; font-size: 14px; font-weight: bold; color: #5d4037;">The Management Team</span>
          </td>
          <td style="border: none; width: 33%;"></td>
        </tr>
      </table>
    </div>
  </div>
</div>';
    }

    private function getAwardTemplateDescription(): string
    {
        return '<style>
@page {
    size: A4-L;
    margin: 0;
}
.award-cert-wrapper {
    width: 297mm;
    height: 210mm;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    background-color: #fcfcfc;
    position: relative;
    border: 8px solid #1a1a1a;
}
.award-inner-border {
    border: 2px solid #d4af37;
    margin: 4px;
    height: 194mm;
    position: relative;
    box-sizing: border-box;
}
.award-top-half {
    background-color: #1a1a1a;
    height: 80mm;
    position: relative;
    padding-top: 10mm;
    text-align: center;
    border-bottom: 4px solid #d4af37;
    box-sizing: border-box;
}
.award-bottom-half {
    height: 110mm;
    padding-top: 15mm;
    text-align: center;
    box-sizing: border-box;
    position: relative;
}
.logo-container {
    text-align: center;
    margin-bottom: 4mm;
    height: 15mm;
}
</style>
<div class="award-cert-wrapper">
  <div class="award-inner-border">
    <div class="award-top-half">
      <!-- Company Logo -->
      <div class="logo-container">
        ##COMPANY_LOGO##
      </div>
      
      <!-- Seal/Badge -->
      <div style="position: absolute; bottom: -15mm; left: 50%; margin-left: -20mm; z-index: 10; width: 40mm; height: 40mm; text-align: center;">
        <svg width="120" height="120" viewBox="0 0 100 100">
          <path d="M35,60 L25,95 L45,85 L50,95 L40,60 Z" fill="#d4af37" opacity="0.9"/>
          <path d="M65,60 L75,95 L55,85 L50,95 L60,60 Z" fill="#aa7c11" opacity="0.9"/>
          <circle cx="50" cy="50" r="32" fill="#d4af37" stroke="#aa7c11" stroke-width="2"/>
          <circle cx="50" cy="50" r="28" fill="#1a1a1a" stroke="#fff" stroke-dasharray="2 2" stroke-width="1.5"/>
          <text x="50" y="46" font-size="8" font-family="\'Georgia\', serif" fill="#ffd700" text-anchor="middle" font-weight="bold" letter-spacing="1">BEST</text>
          <text x="50" y="58" font-size="8" font-family="\'Georgia\', serif" fill="#ffd700" text-anchor="middle" font-weight="bold" letter-spacing="1">AWARD</text>
        </svg>
      </div>

      <h1 style="font-family: \'Georgia\', serif; font-size: 34px; color: #d4af37; letter-spacing: 6px; margin: 0; text-transform: uppercase; font-weight: bold; text-align: center;">Certificate</h1>
      <h2 style="font-family: \'Georgia\', serif; font-size: 13px; color: #ffffff; letter-spacing: 4px; margin: 5px 0 0 0; text-transform: uppercase; text-align: center;">of Authenticity</h2>
    </div>

    <!-- Laurel branch left -->
    <svg width="80" height="200" viewBox="0 0 80 200" style="position: absolute; left: 15mm; top: 85mm; z-index: 1;">
      <path d="M 60,10 Q 30,100 60,190" fill="none" stroke="#d4af37" stroke-width="2.5"/>
      <path d="M 50,30 Q 30,20 20,30 Q 35,40 50,30 Z" fill="#d4af37"/>
      <path d="M 45,60 Q 20,50 15,60 Q 30,70 45,60 Z" fill="#d4af37"/>
      <path d="M 43,90 Q 18,80 12,90 Q 28,100 43,90 Z" fill="#d4af37"/>
      <path d="M 45,120 Q 20,110 15,120 Q 30,130 45,120 Z" fill="#d4af37"/>
      <path d="M 50,150 Q 25,140 20,150 Q 35,160 50,150 Z" fill="#d4af37"/>
      <path d="M 58,20 Q 45,10 40,20 Q 48,30 58,20 Z" fill="#d4af37"/>
      <path d="M 54,50 Q 40,40 35,50 Q 43,60 54,50 Z" fill="#d4af37"/>
      <path d="M 52,80 Q 38,70 33,80 Q 41,90 52,80 Z" fill="#d4af37"/>
      <path d="M 54,110 Q 40,100 35,110 Q 43,120 54,110 Z" fill="#d4af37"/>
      <path d="M 58,140 Q 45,130 40,140 Q 48,150 58,140 Z" fill="#d4af37"/>
    </svg>

    <!-- Laurel branch right -->
    <svg width="80" height="200" viewBox="0 0 80 200" style="position: absolute; right: 15mm; top: 85mm; z-index: 1;">
      <g transform="translate(80,0) scale(-1,1)">
        <path d="M 60,10 Q 30,100 60,190" fill="none" stroke="#d4af37" stroke-width="2.5"/>
        <path d="M 50,30 Q 30,20 20,30 Q 35,40 50,30 Z" fill="#d4af37"/>
        <path d="M 45,60 Q 20,50 15,60 Q 30,70 45,60 Z" fill="#d4af37"/>
        <path d="M 43,90 Q 18,80 12,90 Q 28,100 43,90 Z" fill="#d4af37"/>
        <path d="M 45,120 Q 20,110 15,120 Q 30,130 45,120 Z" fill="#d4af37"/>
        <path d="M 50,150 Q 25,140 20,150 Q 35,160 50,150 Z" fill="#d4af37"/>
        <path d="M 58,20 Q 45,10 40,20 Q 48,30 58,20 Z" fill="#d4af37"/>
        <path d="M 54,50 Q 40,40 35,50 Q 43,60 54,50 Z" fill="#d4af37"/>
        <path d="M 52,80 Q 38,70 33,80 Q 41,90 52,80 Z" fill="#d4af37"/>
        <path d="M 54,110 Q 40,100 35,110 Q 43,120 54,110 Z" fill="#d4af37"/>
        <path d="M 58,140 Q 45,130 40,140 Q 48,150 58,140 Z" fill="#d4af37"/>
      </g>
    </svg>

    <div class="award-bottom-half">
      <p style="font-family: \'Arial\', sans-serif; font-size: 13px; color: #666; letter-spacing: 2px; text-transform: uppercase; margin: 0 auto 5px auto; font-weight: bold; text-align: center;">The Certificate is presented to</p>
      
      <h2 style="font-family: \'Georgia\', serif; font-style: italic; font-size: 38px; color: #111; margin: 5px auto 15px auto; border-bottom: 2px solid #d4af37; display: inline-block; padding-bottom: 5px; width: 60%; text-align: center; line-height: 1.2;">##EMPLOYEE_NAME##</h2>
      
      <p style="font-family: \'Georgia\', serif; font-size: 15px; color: #444; line-height: 24px; max-width: 650px; margin: 10px auto 15px auto; text-align: center;">
        For outstanding performance, dedication, and exemplary hard work that contributes greatly to the success of our organization.
      </p>
      
      <p style="font-family: \'Georgia\', serif; font-size: 14px; color: #b8860b; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; margin: 0 auto 25px auto; text-align: center;">##APPRECIATION_AWARD_NAME##</p>
      
      <table style="width: 90%; border: none; margin: 25px auto 0 auto; border-collapse: collapse;">
        <tr>
          <td style="border: none; width: 45%; text-align: left; vertical-align: bottom; padding-left: 15px;">
            <span style="font-family: \'Arial\', sans-serif; font-size: 11px; color: #666; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">Date:</span>
            <span style="font-family: \'Arial\', sans-serif; font-size: 13px; color: #111; font-weight: bold; border-bottom: 1.5px solid #999; padding-bottom: 2px; display: inline-block; width: 130px; text-align: center;">##APPRECIATION_GIVEN_DATE##</span>
          </td>
          <td style="border: none; width: 10%;"></td>
          <td style="border: none; width: 45%; text-align: right; vertical-align: bottom; padding-right: 15px;">
            <div style="display: inline-block; text-align: center;">
              <div style="border-bottom: 1.5px solid #999; width: 200px; margin-bottom: 5px;"></div>
              <span style="font-family: \'Georgia\', serif; font-size: 14px; font-weight: bold; color: #111;">##SIGNATORY##</span><br/>
              <span style="font-family: \'Arial\', sans-serif; font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px;">##SIGNATORY_DESIGNATION##</span>
            </div>
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>';
    }
};
