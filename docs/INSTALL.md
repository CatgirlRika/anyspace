# Installation & Development Guide

This guide explains how to set up AnySpace for development. It uses short steps and plain language to make it easy to follow.

## What You Need

- PHP 5.3 or newer
- MySQL 5.0 or newer
- A web server (Apache or Nginx)
- Git

## Quick Start

1. **Clone the repo**
   ```bash
   git clone https://example.com/anyspace.git
   cd anyspace
   ```
2. **Copy the configuration**
   ```bash
   cp core/config.php.example core/config.php
   ```
3. **Edit `core/config.php`**
   - Put in your database name, user, and password.
   - Set the site URL.
4. **Create the database**
   ```bash
   mysql -u root -p < schema.sql
   ```
5. **Ensure write access**
   - The web server needs write access to `core/`, `public/media/pfp/`, and `public/media/music/`.
6. **Set up the web server**
   - Point the web root to the `public/` folder.
   - Example configs are in `example.apache` and `example.nginx`.
7. **Visit the site**
   - Open `http://your-site/` in a browser.
   - Create the first user; this becomes the admin by default.

## Useful Commands

Run tests from the project root:
```bash
php tests/forum_permissions.php
php tests/forum_delete.php
```

## What Works Today

- Admin panel
- Login and registration
- Blog, bulletins, and comments
- Friend system
- User profiles with custom HTML/CSS
- Forum module (production-ready discussion board with moderation, notifications, and file attachments; see [forum/README.md](forum/README.md))

## Still in Progress

- Group system
- Private messaging
- Password reset and email verification
- Report and session management

If you get a `PDOException`, a table or column may be missing. Check `schema.sql` and update your database.

## Need Help?

Open an issue or chat with the community. Clear questions get faster answers.

