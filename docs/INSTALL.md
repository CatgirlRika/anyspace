# Installation & Development Guide

This guide explains how to set up AnySpace for development. It uses short steps and plain language to make it easy to follow.

## What You Need

- PHP 5.3 or newer
- MySQL 5.0 or newer
- A web server (Apache or Nginx)
- Git

## Zero-Setup Docker

If you have Docker installed, you can start everything with one command:

```bash
docker compose up
```

This runs a PHP web server and MySQL using the defaults from `.env`.

## Shared Hosting Quick Install

These steps show how to set up AnySpace on a typical shared host with tools like cPanel, DirectAdmin, or ISPConfig.

1. **Download the ZIP**
2. **Upload and extract**
   - In your hosting panel, open the file manager and upload the ZIP. Then extract it, or use SFTP to upload the files.
   - Help guides:
     - [cPanel File Manager](https://docs.cpanel.net/cpanel/files/file-manager/)
     - [DirectAdmin File Manager](https://docs.directadmin.com/userguide/files/file-manager.html)
     - [ISPConfig File Upload](https://www.howtoforge.com/ispconfig-file-upload/)
3. **Create a MySQL database**
   - Use your panel's database tool to make a new database, user, and password. Write them down; you'll need them later.
   - Helpful links:
     - [cPanel MySQL Database Wizard](https://docs.cpanel.net/cpanel/databases/mysql-database-wizard/)
     - [DirectAdmin MySQL](https://docs.directadmin.com/userguide/databases/mysql.html)
     - [ISPConfig MySQL](https://www.howtoforge.com/ispconfig-creating-a-mysql-database/)
4. **Run the installer**
   - Go to `http://your-site/public/install.php` in your web browser.
   - Fill in the database details and follow the on-screen steps to finish.

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
- Forum module

## Still in Progress

- Group system
- Private messaging
- Password reset and email verification
- Report and session management

If you get a `PDOException`, a table or column may be missing. Check `schema.sql` and update your database.

## Need Help?

Open an issue or chat with the community. Clear questions get faster answers.

