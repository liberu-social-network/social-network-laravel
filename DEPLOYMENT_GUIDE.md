# Deployment Guide for Media Sharing Feature

This guide provides step-by-step instructions for deploying the media sharing and gallery feature to production.

## Prerequisites

- PHP 8.3 or higher
- MySQL 5.7 or higher
- Composer installed
- Laravel 12.x
- Storage directory with write permissions

## Deployment Steps

### 1. Pull Latest Code

```bash
git checkout copilot/add-media-upload-functionality
git pull origin copilot/add-media-upload-functionality
```

### 2. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Run Database Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `media` - Stores media files
- `albums` - Stores user albums
- `tags` - Stores media tags
- `media_tag` - Pivot table for media-tag relationships
- Adds `privacy` column to `posts` table

### 4. Set Up Storage

Create the storage link if not already exists:

```bash
php artisan storage:link
```

Ensure proper permissions:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 5. Configure File Upload Limits

Update `php.ini` or `.htaccess` to allow larger file uploads:

**php.ini:**
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

**Laravel .env:**
```env
# Optional: Configure cloud storage (AWS S3, etc.)
FILESYSTEM_DISK=public  # or 's3' for cloud storage

# If using S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_URL=
```

### 6. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

Then cache for production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Run Tests (Optional but Recommended)

Before deploying to production, run the test suite:

```bash
php artisan test --filter MediaTest
php artisan test --filter AlbumTest
php artisan test --filter PostPrivacyTest
```

Expected: All 29 tests should pass.

### 8. Optimize Autoloader

```bash
composer dump-autoload --optimize
```

### 9. Queue Worker (Optional)

If you plan to add background processing for thumbnails or image optimization:

```bash
php artisan queue:work --daemon
```

Consider using Supervisor to keep the queue worker running.

## Configuration

### Storage Configuration

The feature uses Laravel's Storage facade. Default configuration is in `config/filesystems.php`:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

### File Validation Rules

Current validation rules in `MediaController`:
- Images: `jpeg,jpg,png,gif`
- Videos: `mp4,mov,avi,wmv`
- Max size: 50MB (51200 KB)

To modify, update the validation rules in:
- `app/Http/Controllers/MediaController.php` (line ~62)

## Post-Deployment Verification

### 1. Test Media Upload

```bash
# Using curl
curl -X POST http://your-domain.com/api/media \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "privacy=public" \
  -F "description=Test upload"
```

### 2. Test Album Creation

```bash
curl -X POST http://your-domain.com/api/albums \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Album",
    "description": "Testing album creation",
    "privacy": "public"
  }'
```

### 3. Verify Storage

Check that files are being saved:

```bash
ls -la storage/app/public/media/images/
ls -la storage/app/public/media/videos/
```

### 4. Test Privacy Settings

Create posts/media with different privacy levels and verify visibility:
- Create public post → Should be visible to all
- Create friends_only post → Should only be visible to friends
- Create private post → Should only be visible to owner

## Troubleshooting

### Issue: 413 Request Entity Too Large

**Solution:** Increase nginx/Apache upload limits

**Nginx:**
```nginx
client_max_body_size 50M;
```

**Apache (.htaccess):**
```apache
php_value upload_max_filesize 50M
php_value post_max_size 50M
```

### Issue: Storage link not working

**Solution:**
```bash
# Remove existing link if corrupted
rm public/storage

# Recreate
php artisan storage:link
```

### Issue: Permission denied errors

**Solution:**
```bash
# Fix ownership
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# Fix permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Issue: Migration errors

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Rollback if needed
php artisan migrate:rollback

# Re-run migrations
php artisan migrate
```

## Monitoring

### Log Files to Monitor

- `storage/logs/laravel.log` - Application logs
- Web server error logs (nginx/Apache)

### Key Metrics to Track

1. **Storage Usage**
   - Monitor disk space in `storage/app/public/media/`
   - Set up alerts for low disk space

2. **Upload Success Rate**
   - Track successful vs failed uploads
   - Monitor validation errors

3. **Privacy Checks**
   - Ensure privacy settings are being enforced
   - Monitor unauthorized access attempts

## Rollback Plan

If issues arise, you can rollback:

```bash
# Rollback migrations
php artisan migrate:rollback --step=5

# Switch back to previous branch
git checkout main

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Performance Optimization

### 1. Enable OPcache

Add to `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### 2. Database Indexes

Migrations already include indexes on:
- `media.user_id`
- `media.album_id`
- `media.privacy`
- `albums.user_id`
- `albums.privacy`

### 3. Image Optimization (Future Enhancement)

Consider adding image optimization packages:
```bash
composer require intervention/image
```

### 4. CDN Integration (Future Enhancement)

For high-traffic sites, consider using a CDN:
- AWS CloudFront
- Cloudflare
- DigitalOcean Spaces

## Security Checklist

- [ ] File upload validation is working
- [ ] Privacy settings are enforced
- [ ] Authorization checks prevent unauthorized access
- [ ] Files are stored with random names
- [ ] HTTPS is enabled (SSL certificate)
- [ ] CSRF protection is active
- [ ] Rate limiting is configured
- [ ] Regular backups are scheduled

## Backup Strategy

### Database Backup

```bash
# Daily backup script
mysqldump -u user -p database_name > backup_$(date +%Y%m%d).sql
```

### Media Files Backup

```bash
# Backup media files
tar -czf media_backup_$(date +%Y%m%d).tar.gz storage/app/public/media/
```

### Automated Backups

Consider using:
- Laravel Backup package
- Cloud backup solutions (AWS Backup, Google Cloud Backup)
- Cron jobs for regular backups

## Support and Maintenance

### Regular Maintenance Tasks

1. **Monthly:**
   - Review storage usage
   - Clean up orphaned files
   - Check error logs

2. **Quarterly:**
   - Review and update file validation rules
   - Optimize database indexes
   - Review privacy settings effectiveness

3. **Annually:**
   - Security audit
   - Performance review
   - Update dependencies

## Additional Resources

- **Feature Documentation:** `docs/MEDIA_SHARING_FEATURE.md`
- **Implementation Summary:** `MEDIA_IMPLEMENTATION_SUMMARY.md`
- **Laravel Storage Documentation:** https://laravel.com/docs/filesystem
- **API Endpoints:** See feature documentation

## Contact

For issues or questions:
1. Check the documentation files
2. Review test files for usage examples
3. Check Laravel logs for errors
4. Open an issue on GitHub repository

---

**Last Updated:** 2026-02-15
**Version:** 1.0.0
**Status:** Production Ready ✅
