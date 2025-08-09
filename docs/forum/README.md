# AnySpace Forums

This module provides a complete, production-ready discussion board with hierarchical categories, forums, and topics. It follows AnySpace's modular patterns and reuses existing helpers for sessions and sanitization.

## Production Ready Features

### ✅ Security Features
- **CSRF Protection**: All forms protected with CSRF tokens
- **Rate Limiting**: Prevents spam with configurable post limits (3 posts per minute for members)
- **Input Validation**: Comprehensive XSS protection and HTML sanitization
- **Word Filtering**: Automatic detection and handling of filtered content
- **User Permissions**: Granular role-based access control
- **Ban System**: User banning with automatic session enforcement
- **Audit Logging**: Complete activity logs for security monitoring

### ✅ Performance Features
- **Statistics Caching**: File-based caching for forum statistics (5-minute TTL)
- **Optimized Queries**: Efficient database queries with proper indexing
- **Cache Invalidation**: Automatic cache clearing on content updates

### ✅ User Experience
- **Responsive Design**: Mobile-friendly forum interface
- **Search Functionality**: Full-text search across topics and posts
- **Notifications**: Real-time notifications for replies and mentions
- **Subscriptions**: Topic subscription system
- **File Attachments**: Support for file uploads on posts
- **Rich Content**: HTML content support with safe sanitization

### ✅ Moderation Tools
- **Comprehensive Dashboard**: Statistics and recent activity overview
- **Post Management**: Lock, delete, restore posts and topics
- **User Management**: Ban/unban users with moderation logging
- **Report System**: Community reporting with resolution tracking
- **Word Filter Management**: Admin interface for filtered words
- **Activity Logs**: Complete audit trail of all moderator actions

## Directory Layout

```
admin/forum/           Admin pages for managing categories, forums, moderators, permissions, and word filters
core/forum/            Production-ready helpers for all forum functionality:
                       ├── category.php        - Category CRUD operations
                       ├── forum.php          - Forum management with statistics caching
                       ├── topic.php          - Topic operations with moderation
                       ├── post.php           - Post creation with rate limiting and logging
                       ├── permissions.php    - Role-based access control
                       ├── rate_limit.php     - Anti-spam rate limiting
                       ├── audit_log.php      - Security and activity logging
                       ├── notifications.php  - User notification system
                       ├── subscriptions.php  - Topic subscription management
                       ├── word_filter.php    - Content filtering system
                       ├── mod_log.php        - Moderation action logging
                       └── ... (additional security and feature modules)
public/forum/          User-facing forum pages with complete functionality:
                       ├── forums.php         - Category and forum listing
                       ├── topic.php          - Topic viewing with moderation tools
                       ├── post.php           - Post viewing and quick reply
                       ├── new_post.php       - Post creation with file uploads
                       ├── search.php         - Search interface
                       ├── mod/               - Moderator dashboard and tools
                       └── ... (additional user interfaces)
tests/                 Comprehensive test suite covering all functionality
 ```

## Database Tables

The schema extends `schema.sql` with production-ready tables:

| Table | Purpose | Features |
|-------|---------|----------|
| `forum_categories` | Top-level containers for forums | Ordering via `position` |
| `forums` | Individual forums and subforums | Hierarchical structure, statistics caching |
| `forum_topics` | Discussion threads | Lock/sticky/move support, subscription tracking |
| `forum_posts` | Messages within topics | Soft deletion, rate limiting, attachment support |
| `forum_permissions` | Role-based access control | View/post/moderate permissions per forum |
| `forum_moderators` | Explicit moderator assignments | Per-forum moderation rights |
| `reports` | Community reports | Open/closed status with resolution tracking |
| `mod_log` | Audit log of moderator actions | Complete action history |
| `bad_words` | Terms blocked by word filter | Configurable content filtering |
| `notifications` | Reply and mention notifications | Real-time user alerts |
| `topic_subscriptions` | User topic subscriptions | Automatic notification triggers |
| `attachments` | File attachments on posts | Secure file upload management |
| `post_reactions` | User reactions to posts | Like/dislike system |
| `polls` | Topic polls | Voting system with results |

## Core Production Features

### Security & Anti-Spam
* **Rate Limiting** – `rate_limit.php` prevents spam (configurable: 3 posts/minute)
* **CSRF Protection** – All forms use secure tokens
* **Input Sanitization** – HTML validation prevents XSS
* **Word Filtering** – Automatic content moderation
* **Ban System** – User banning with session enforcement
* **Audit Logging** – Complete activity monitoring

### Performance & Scalability
* **Statistics Caching** – File-based cache (5-min TTL) for forum stats
* **Optimized Queries** – Efficient database operations
* **Cache Management** – Automatic invalidation on updates

### User Features
* **Search** – Full-text search across topics and posts
* **Notifications** – Alerts for replies and @mentions
* **Subscriptions** – Topic following with notifications
* **File Uploads** – Attachment support for posts
* **Responsive UI** – Mobile-friendly interface

### Moderation Features
* **Dashboard** – Statistics and activity overview
* **Content Management** – Lock, delete, restore posts/topics
* **User Management** – Ban/unban with logging
* **Report Queue** – Community reporting system
* **Word Filter** – Configurable content filtering
* **Action Logs** – Complete moderation audit trail

## Admin Workflow

Admin pages in `admin/forum/` are production-ready with comprehensive functionality:

* `categories.php` – Category management with ordering
* `forums.php` – Forum management with statistics
* `moderators.php` – Moderator assignment per forum
* `permissions.php` – Granular permission management
* `word_filter.php` – Content filtering configuration
* `reports.php` – Community report management

All admin actions include logging and flash message feedback.

## Testing

Comprehensive test suite covering all production features:

```bash
# Run all forum tests
for t in tests/forum*.php; do php "$t"; done

# Specific feature tests
php tests/forum_rate_limit.php      # Rate limiting and anti-spam
php tests/forum_permissions.php     # Access control
php tests/forum_notifications.php   # User notifications
php tests/forum_subscriptions.php   # Topic subscriptions
php tests/forum_word_filter.php     # Content filtering
php tests/forum_bans.php           # User ban system
php tests/forum_mod_log.php        # Moderation logging
php tests/forum_improvements.php   # Performance features
```

All tests use SQLite for isolation and verify both functionality and security.

## Production Deployment

### Requirements
- PHP >= 5.3 with PDO support
- MySQL/MariaDB database
- Web server (Apache/Nginx) with mod_rewrite
- Writable `cache/` directory for statistics caching
- File upload support configured

### Security Checklist
- [ ] Configure rate limiting thresholds per your needs
- [ ] Set up log monitoring for security events
- [ ] Configure file upload restrictions
- [ ] Review and customize word filter list
- [ ] Set appropriate moderator permissions
- [ ] Enable database query logging for performance monitoring
- [ ] Configure backup strategy for user content

### Performance Optimization
- [ ] Enable opcode caching (OPcache)
- [ ] Configure database indexes for large datasets
- [ ] Set up log rotation for audit logs
- [ ] Monitor cache directory size and cleanup
- [ ] Configure CDN for attachment delivery (if needed)

## Monitoring & Maintenance

### Log Files
- `admin_logs.txt` – Forum activity and security events
- System error log – PHP errors and security warnings

### Cache Management
- Statistics cache: `cache/forum_stats_*.json`
- Automatic cleanup with 5-minute TTL
- Manual cache clearing available through admin interface

### Performance Metrics
- Post creation rate and rate limiting effectiveness
- Cache hit rates for statistics
- Database query performance
- User activity patterns

## Security Features in Detail

### Rate Limiting
- **Members**: 3 posts per minute maximum
- **Bypass**: Moderators and admins exempt
- **Logging**: All rate limit violations logged
- **User Feedback**: Clear error messages with countdown

### Content Security
- **HTML Sanitization**: Removes dangerous scripts and tags
- **Word Filtering**: Configurable blocked word list
- **CSRF Protection**: All forms include secure tokens
- **File Upload**: Restricted file types and size limits

### Access Control
- **Role-based**: Guest, Member, Moderator, Admin levels
- **Per-forum**: Granular view/post/moderate permissions
- **Session Management**: Automatic ban enforcement
- **Login Requirements**: Protected actions require authentication

This forum module is fully production-ready with enterprise-level security, performance, and moderation features.
