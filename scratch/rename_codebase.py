import os

replacements = [
    (".env", "HrmiflySaas", "iBridge HR"),
    (".env.superadmin", "HrmiflySaas", "iBridge HR"),
    ("routes/main.php", '"Hrmifly"', '"iBridge HR"'),
    ("app/SuperAdmin/routes/main.php", "'Hrmifly SAAS'", "'iBridge HR'"),
    ("app/SuperAdmin/Classes/SuperAdminCommon.php", "'Hrmifly SAAS'", "'iBridge HR'"),
    ("resources/views/vendor/installer/layouts/master.blade.php", "Hrmifly", "iBridge HR"),
    ("resources/js/main/store/authStore.js", '"Hrmifly SAAS"', '"iBridge HR"'),
    ("resources/js/main/views/front/layouts/JobDetails.vue", "Hrmifly", "iBridge HR"),
    ("resources/js/main/views/front/layouts/ApplicationForm.vue", "Hrmifly", "iBridge HR"),
    ("database/seeders/CompanyTableSeeder.php", "'Hrmifly'", "'iBridge HR'"),
    ("database/migrations/2021_01_02_193007_create_companies_table.php", "'Hrmifly'", "'iBridge HR'"),
    ("public/manifest.json", '"Hrmifly SAAS"', '"iBridge HR"')
]

for file_path, old_text, new_text in replacements:
    if os.path.exists(file_path):
        with open(file_path, "r", encoding="utf-8") as f:
            content = f.read()
        
        new_content = content.replace(old_text, new_text)
        
        if content != new_content:
            with open(file_path, "w", encoding="utf-8") as f:
                f.write(new_content)
            print(f"Updated: {file_path}")
        else:
            print(f"No match found for '{old_text}' in {file_path}")
    else:
        print(f"File not found: {file_path}")

print("Codebase replace complete.")
