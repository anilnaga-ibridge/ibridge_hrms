import sys
import os

files_to_revert = [
    ".env",
    ".env.superadmin",
    "routes/main.php",
    "app/SuperAdmin/routes/main.php",
    "app/SuperAdmin/Classes/SuperAdminCommon.php",
    "database/seeders/CompanyTableSeeder.php",
    "database/migrations/2021_01_02_193007_create_companies_table.php",
    "resources/js/main/views/front/layouts/ApplicationForm.vue",
    "resources/js/main/views/front/layouts/JobDetails.vue",
    "resources/js/main/store/authStore.js",
    "public/manifest.json"
]

for file_path in files_to_revert:
    if os.path.exists(file_path):
        with open(file_path, "r", encoding="utf-8") as f:
            content = f.read()
        
        new_content = content.replace("iBridge HR", "Hrmifly")
        
        if content != new_content:
            with open(file_path, "w", encoding="utf-8") as f:
                f.write(new_content)
            print(f"Reverted: {file_path}")
    else:
        print(f"File not found: {file_path}")

print("Revert complete.")
