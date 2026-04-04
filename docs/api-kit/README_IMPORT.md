# API Kit Import Guide

Files in this folder:
- `API_CONTRACT.md`
- `thunder_collection.json`
- `thunder_environment.json`
- `postman_collection.json`
- `postman_environment.json`

## Thunder Client (VS Code)
1. Open Thunder Client sidebar.
2. Click Settings (gear icon) -> Import.
3. Import `thunder_environment.json` first.
4. Import `thunder_collection.json`.
5. Select environment `Grade Dispute Local`.

## Postman
1. Open Postman -> Import.
2. Import `postman_environment.json`.
3. Import `postman_collection.json`.
4. Select environment `Grade Dispute Local`.

## Variables to adjust
- `base_url`
- `student_identifier`
- `lecturer_identifier`
- `admin_identifier`
- `dispute_id`
- `notification_id`

## Notes
- Protected routes require `X-User-Identifier` header.
- The collections include required headers on protected requests.
- For local backend testing run: `php artisan serve`.
