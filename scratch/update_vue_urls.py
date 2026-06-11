import os

files = [
    '/Applications/MAMP/htdocs/hrm/resources/js/main/views/front/layouts/JobDetails.vue',
    '/Applications/MAMP/htdocs/hrm/resources/js/main/views/front/layouts/ApplicationForm.vue'
]

for file_path in files:
    with open(file_path, 'r') as f:
        content = f.read()

    # Replace external image with config path
    content = content.replace('src="https://demo.hrmifly.in/images/dark.png"', ':src="appSetting?.light_logo_url || \'/images/dark.png\'"')
    content = content.replace('url(\'https://demo.hrmifly.in/images/login_background.svg\')', 'url(\'/images/login_background.svg\')')
    
    with open(file_path, 'w') as f:
        f.write(content)
        
print("Updated Vue files to remove hrmifly external links.")
