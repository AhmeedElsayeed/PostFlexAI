# API Documentation

## Authentication

### Password Reset
- `POST /api/password/forgot` - Request password reset
  - Body: `{ "email": "user@example.com" }`
  - Response: `{ "message": "Password reset instructions sent to your email" }`

- `POST /api/password/reset` - Reset password with token
  - Body: `{ "token": "reset-token", "password": "new-password", "password_confirmation": "new-password" }`
  - Response: `{ "message": "Password reset successfully" }`

### Two-Factor Authentication
- `POST /api/2fa/enable` - Enable 2FA
  - Requires authentication
  - Response: `{ "message": "2FA setup initiated", "data": { "secret": "...", "qr_code": "..." } }`

- `POST /api/2fa/verify` - Verify 2FA setup
  - Requires authentication
  - Body: `{ "code": "123456" }`
  - Response: `{ "message": "2FA enabled successfully" }`

- `POST /api/2fa/disable` - Disable 2FA
  - Requires authentication
  - Response: `{ "message": "2FA disabled successfully" }`

- `POST /api/2fa/verify-backup` - Verify backup code
  - Requires authentication
  - Body: `{ "code": "backup-code" }`
  - Response: `{ "message": "Backup code verified successfully" }`

## Backup Management
- `GET /api/backups` - List all backups
  - Requires admin role
  - Response: `{ "backups": [...], "storage_used": 123456, "retention_days": 7 }`

- `POST /api/backups/create` - Create new backup
  - Requires admin role
  - Response: `{ "message": "Backup created successfully" }`

- `GET /api/backups/download/{filename}` - Download backup file
  - Requires admin role
  - Response: File download

- `DELETE /api/backups/{filename}` - Delete backup
  - Requires admin role
  - Response: `{ "message": "Backup deleted successfully" }`

## Security Notes
- All API endpoints are protected with rate limiting
- Session timeout is set to 30 minutes
- Passwords must be at least 8 characters long
- 2FA backup codes are 10 characters long
- Backup files are retained for 7 days by default 