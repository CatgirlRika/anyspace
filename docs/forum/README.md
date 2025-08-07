# AnySpace Forums

This module provides a classic MySpace‑style discussion board with hierarchical categories, forums, and topics.  It follows AnySpace's modular patterns and reuses existing helpers for sessions and sanitization.

## Directory Layout

```
admin/forum/           Admin pages for managing categories, forums, moderators, and permissions
core/forum/            Reusable helpers for categories, forums, topics, posts, and permission checks
public/forum/          (future) User‑facing forum pages
tests/                 Regression tests for forum helpers
```

## Database Tables

The schema extends `schema.sql` with tables below:

| Table | Purpose |
|-------|---------|
| `forum_categories` | Top‑level containers for forums; ordered via `position` |
| `forums` | Individual forums and subforums; reference a category and optional parent forum |
| `forum_topics` | Discussion threads inside a forum; support `locked`, `sticky`, and `moved_to` flags |
| `forum_posts` | Messages within a topic; soft deletions tracked with `deleted`, `deleted_by`, and `deleted_at` |
| `forum_permissions` | Role‑based flags (`can_view`, `can_post`, `can_moderate`) per forum |
| `forum_moderators` | Explicit moderator assignments for a forum |

## Core Helpers

* `category.php` – CRUD helpers for categories
* `forum.php` – CRUD helpers for forums plus recursive deletion of subforums, topics, posts, and permission records
* `topic.php` – Topic utilities including creation, moving, locking/unlocking, stickying, and audit logging
* `post.php` – Post creation, soft deletion/restore, quoting, and list retrieval with optional deleted posts
* `permissions.php` – Centralized permission layer combining role checks, forum‑specific assignments, and `login_check()` redirects

All helpers assume a global `$conn` PDO connection and leverage `validateContentHTML()` for input sanitization.

## Admin Workflow

Admin pages in `admin/forum/` reuse the site's admin header/footer and are guarded by `admin_only()`:

* `categories.php` – List, create, edit, and delete categories
* `forums.php` – Manage forums and subforums, including position, description, and recursive deletion
* `moderators.php` – Assign or revoke forum‑specific moderators
* `global_mods.php` – Promote or demote users to the global moderator role (`users.rank = 1`)
* `permissions.php` – Set role‑based permissions for each forum

After any action the pages redirect back with a `msg` query parameter to display flash messages.

## Moderator Tools

Moderators and admins can perform additional actions:

* **Lock/Unlock topics** – Prevent new posts when `locked = 1`
* **Sticky/Unsticky topics** – Pin important topics to the top of listings
* **Move topics** – Relocate threads to a different forum while leaving a locked placeholder
* **Soft delete/restore posts** – Hide content without permanent deletion; deleted posts are excluded from standard views
* **Quote posts** – `post_get_quote()` returns BBCode/HTML for embedding in replies

Global moderators and forum‑specific moderators automatically gain `can_moderate` rights without explicit permission entries.

## Testing

Forum helpers ship with lightweight regression tests:

```
php tests/forum_permissions.php
php tests/forum_delete.php
```

These scripts run against in‑memory SQLite databases to verify permission handling and recursive deletions.

## Next Steps

* Build user‑facing pages under `public/forum/` for browsing categories, viewing topics, and posting messages
* Apply the planned MySpace‑style theme (`public/static/css/forum.css`)
* Expand tests and add integration coverage once public pages exist

