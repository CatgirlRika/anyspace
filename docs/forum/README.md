# AnySpace Forums

This module provides a classic MySpace‑style discussion board with hierarchical categories, forums, topics, and posts.
It follows AnySpace's modular patterns and reuses existing helpers for sessions and input sanitization.

## Directory Layout

```
admin/forum/           Admin pages for managing categories, forums, moderators, permissions, and word filters
core/forum/            Reusable helpers for categories, forums, topics, posts, reporting, notifications,
                       and permission checks
public/forum/          User-facing forum pages for browsing categories, topics, posts, search results,
                       and moderator tools
tests/                 Regression and integration tests for forum helpers and pages
```

## Database Tables

The schema extends `schema.sql` with tables below:

| Table | Purpose |
|-------|---------|
| `forum_categories` | Top‑level containers for forums; ordered via `position` |
| `forums` | Individual forums and subforums; reference a category and optional parent forum |
| `forum_topics` | Discussion threads inside a forum; support `locked`, `sticky`, and `moved_to` flags |
| `forum_posts` | Messages within a topic; soft deletions tracked with `deleted`, `deleted_by`, and `deleted_at` |
| `forum_permissions` | Role-based flags (`can_view`, `can_post`, `can_moderate`) per forum |
| `forum_moderators` | Explicit moderator assignments for a forum |
| `reports` | Community reports on posts, topics, or users with open/closed status |
| `mod_log` | Audit log of moderator actions |
| `bad_words` | Terms blocked by the automatic word filter |
| `notifications` | Reply and mention notifications for users |

## Core Helpers

* `category.php` – CRUD helpers for categories
* `forum.php` – CRUD helpers for forums plus recursive deletion of subforums, topics, posts, and permission records
* `topic.php` – Topic utilities including creation, moving, locking/unlocking, stickying, and audit logging
* `post.php` – Post creation, editing, soft deletion/restore, quoting, search integration, notification triggers,
  and list retrieval with optional deleted posts
* `permissions.php` – Centralized permission layer combining role checks, forum-specific assignments,
  and `login_check()` redirects
* `report.php` – Submit and resolve community reports
* `mod_log.php` – Record moderator actions
* `ban.php` – Ban and unban users by setting `banned_until`
* `word_filter.php` – Maintain and check the list of filtered words
* `notifications.php` – Track unread reply and mention notifications
* `mod_dashboard.php` – Collect statistics for the moderation dashboard

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
* **Soft delete/restore posts** – Hide content without permanent deletion; deleted posts are excluded
  from standard views
* **Quote posts** – `post_get_quote()` returns BBCode/HTML for embedding in replies
* **Report queue** – Users can report posts or topics; moderators review and resolve them
* **Action log** – Every moderation decision is stored in `mod_log` for auditing
* **Ban/unban users** – Quickly restrict accounts by setting `banned_until`
* **Word filter** – Posts are checked against a configurable list of banned words
* **Moderation dashboard** – Overview of open reports, active bans, and recent actions

Global moderators and forum‑specific moderators automatically gain `can_moderate` rights without
explicit permission entries.

## User Features

* **Search** – Find topics and posts by keyword
* **Notifications** – Users receive alerts for replies or @mentions

## Testing

Forum helpers ship with lightweight regression and integration tests in the `tests/` directory.
They run against SQLite databases via the `DB_DSN` environment variable and cover permissions,
deletion cascades, public rendering, reporting, moderation logs, bans, word filtering, search,
notifications, and more:

```
php tests/forum_permissions.php
php tests/forum_delete.php
php tests/forum_public.php
php tests/forum_reports.php
php tests/forum_mod_log.php
php tests/forum_bans.php
php tests/forum_word_filter.php
php tests/forum_search.php
php tests/forum_notifications.php
php tests/forum_mod_dashboard.php
php tests/forum_topics.php
php tests/forum_posts.php
```

## Next Steps

* Broaden integration coverage to topic and post flows
* Polish user‑facing pages and styling

