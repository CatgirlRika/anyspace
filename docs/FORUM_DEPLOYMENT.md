# Forum Module Production Deployment Guide

This guide covers deploying the AnySpace forum module in a production environment with all security and performance features enabled.

## Pre-Deployment Checklist

### System Requirements
- [x] PHP >= 5.3 with PDO MySQL extension
- [x] MySQL/MariaDB >= 5.0
- [x] Web server (Apache/Nginx) with mod_rewrite
- [x] Writable directories: `cache/`, `public/uploads/forum/`
- [x] File upload support enabled in PHP

### Security Configuration
- [x] Set secure file upload limits in `php.ini`
- [x] Configure proper directory permissions
- [x] Enable error logging for monitoring
- [x] Review default rate limiting settings
- [x] Configure word filter for your community

## Installation Steps

### 1. Database Setup
Ensure the forum tables are created by running the installation script or importing `schema.sql`. Key tables include:
- `forum_categories`, `forums`, `forum_topics`, `forum_posts`
- `forum_permissions`, `forum_moderators`
- `notifications`, `topic_subscriptions`
- `bad_words`, `mod_log`, `reports`

### 2. File System Setup
```bash
# Create cache directory for statistics
mkdir -p cache
chmod 755 cache

# Create forum upload directory
mkdir -p public/uploads/forum
chmod 755 public/uploads/forum

# Ensure log files are writable
touch admin_logs.txt
chmod 644 admin_logs.txt
```

### 3. Configuration

#### Rate Limiting (in `core/forum/rate_limit.php`)
```php
// Default: 3 posts per minute for members
// Adjust based on your community size and needs
function forum_rate_limit_exceeded($user_id, $limit_minutes = 1, $max_posts = 3)
```

#### Statistics Caching (in `core/forum/forum.php`)
```php
// Default: 5-minute cache TTL
// Increase for high-traffic sites, decrease for real-time updates
$cache_ttl = 300; // 5 minutes
```

#### File Upload Limits
Configure in `php.ini`:
```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 15M
max_execution_time = 60
```

### 4. Admin Setup

1. **Create Categories**: Use `admin/forum/categories.php`
2. **Create Forums**: Use `admin/forum/forums.php`
3. **Set Permissions**: Configure in `admin/forum/permissions.php`
4. **Assign Moderators**: Use `admin/forum/moderators.php`
5. **Configure Word Filter**: Set up in admin dashboard

## Production Features

### Security Features Active
✅ **CSRF Protection**: All forms protected  
✅ **Rate Limiting**: 3 posts/minute for members  
✅ **Input Sanitization**: XSS protection enabled  
✅ **Word Filtering**: Configurable content moderation  
✅ **User Permissions**: Role-based access control  
✅ **Ban System**: Automatic session enforcement  
✅ **Audit Logging**: Complete activity monitoring  

### Performance Features Active
✅ **Statistics Caching**: 5-minute TTL file cache  
✅ **Optimized Queries**: Efficient database operations  
✅ **Cache Invalidation**: Automatic on content updates  

### User Features Active
✅ **Search**: Full-text search across content  
✅ **Notifications**: Real-time alerts for replies/@mentions  
✅ **Subscriptions**: Topic following with notifications  
✅ **File Uploads**: Attachment support for posts  
✅ **Responsive UI**: Mobile-friendly interface  

### Moderation Features Active
✅ **Dashboard**: Statistics and activity overview  
✅ **Content Management**: Lock/delete/restore posts/topics  
✅ **User Management**: Ban/unban with logging  
✅ **Report Queue**: Community reporting system  
✅ **Word Filter**: Real-time content filtering  
✅ **Action Logs**: Complete moderation audit trail  

## Monitoring & Maintenance

### Log Monitoring
Monitor these files for security and performance issues:
- `admin_logs.txt` - Forum activity and security events
- PHP error log - System errors and warnings
- Web server access/error logs - HTTP-level issues

### Key Security Events to Monitor
- `SECURITY: rate_limit_exceeded` - Potential spam attempts
- `SECURITY: filtered_post_created` - Content filter triggers
- `MODERATION: *` - All moderation actions
- Login failures and ban attempts

### Performance Monitoring
- Cache hit rates in `cache/forum_stats_*.json`
- Database query performance
- File upload success rates
- User activity patterns

### Regular Maintenance Tasks

#### Daily
- Monitor security logs for suspicious activity
- Check cache directory size (should auto-manage)
- Review community reports in admin dashboard

#### Weekly
- Review moderation logs for patterns
- Clean up old notification data (optional)
- Monitor database performance

#### Monthly
- Rotate log files if they grow large
- Review and update word filter list
- Analyze user activity trends
- Backup user content and configurations

## Troubleshooting

### Common Issues

#### "Rate limit exceeded" errors
- Check if rate limits are too strict for your community
- Verify moderator exemptions are working
- Review logs for spam patterns

#### Cache directory permissions
```bash
# Fix permissions if caching fails
chmod 755 cache
chown www-data:www-data cache  # Adjust user as needed
```

#### File upload failures
- Check `upload_max_filesize` in PHP configuration
- Verify `public/uploads/forum/` is writable
- Review file type restrictions in upload code

#### Database performance
- Add indexes if queries are slow with large datasets
- Monitor MySQL slow query log
- Consider connection pooling for high traffic

### Performance Tuning

#### For High Traffic Sites
1. Increase statistics cache TTL to 15-30 minutes
2. Implement Redis/Memcached for distributed caching
3. Add database read replicas for SELECT queries
4. Use CDN for file attachments
5. Implement database query optimization

#### For Small Communities
1. Decrease cache TTL to 1-2 minutes for real-time feel
2. Relax rate limiting (5-10 posts/minute)
3. Enable more detailed logging for community management

## Security Best Practices

### Access Control
- Regularly review moderator permissions
- Monitor admin access logs
- Use strong passwords for admin accounts
- Consider two-factor authentication for moderators

### Content Security
- Keep word filter updated for your community
- Monitor filtered content for false positives
- Review user reports promptly
- Log all moderation actions

### System Security
- Keep PHP and MySQL updated
- Use HTTPS for all forum access
- Regular backup of forum content
- Monitor for SQL injection attempts

## Support & Documentation

For additional help:
- Review `docs/forum/README.md` for technical details
- Check `tests/forum_*.php` for functionality examples
- Monitor community feedback for feature requests
- Consult web server and PHP documentation for performance tuning

The forum module is designed to be production-ready with enterprise-level features for security, performance, and moderation.