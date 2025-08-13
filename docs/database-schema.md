# Database Schema

| Table Name | Purpose | Key Fields | Relationships |
| --- | --- | --- | --- |
| users | Stores user account information | `id`, `username`, `email`, `password` | Related to most other tables |
| favorites | Tracks user favorites | `user_id`, `favorites` | `user_id` → `users.id` |
| friends | Friend requests and status | `sender`, `receiver`, `status` | `sender`/`receiver` → `users.id` |
| sessions | Active user sessions | `user_id`, `session_id`, `last_logon` | `user_id` → `users.id` |
| blogs | User-created blog posts | `id`, `text`, `author`, `title`, `date` | `author` → `users.id`; comments in `blogcomments` |
| blogcomments | Comments on blogs | `toid`, `parent_id`, `author`, `text` | `toid` → `blogs.id` |
| bulletins | Public announcements | `id`, `text`, `author`, `title`, `date` | `author` → `users.id`; comments in `bulletincomments` |
| bulletincomments | Comments on bulletins | `toid`, `parent_id`, `author`, `text` | `toid` → `bulletins.id` |
| groups | User-created groups | `id`, `name`, `description`, `author` | `author` → `users.id`; comments in `groupcomments` |
| groupcomments | Comments on groups | `toid`, `author`, `text` | `toid` → `groups.id` |
| layouts | Stored profile layouts | `id`, `text`, `author`, `title`, `code` | `author` → `users.id`; comments in `layoutcomments` |
| layoutcomments | Comments on layouts | `toid`, `author`, `text` | `toid` → `layouts.id` |
| messages | Direct user messages | `id`, `toid`, `author`, `msg` | `author`/`toid` → `users.id` |
| reports | Content reports for moderation | `id`, `reported_id`, `type`, `reason`, `reporter_id`, `status` | `reporter_id` → `users.id` |
| forum_categories | Containers for forums | `id`, `name`, `position` | referenced by `forums.category_id` |
| forums | Forums and subforums | `id`, `category_id`, `parent_forum_id`, `name` | `category_id` → `forum_categories.id` |
| forum_topics | Discussion threads | `id`, `forum_id`, `user_id`, `title` | `forum_id` → `forums.id`, `user_id` → `users.id` |
| forum_posts | Posts within topics | `id`, `topic_id`, `user_id`, `body` | `topic_id` → `forum_topics.id`, `user_id` → `users.id` |
| attachments | File attachments for posts | `id`, `post_id`, `path`, `mime_type` | `post_id` → `forum_posts.id` |
| forum_permissions | Role-based flags per forum | `forum_id`, `role`, `can_view`, `can_post`, `can_moderate` | `forum_id` → `forums.id` |
| forum_moderators | Moderator assignments | `forum_id`, `user_id` | `forum_id` → `forums.id`, `user_id` → `users.id` |
| notifications | Reply and mention notifications | `id`, `user_id`, `post_id`, `is_read` | `user_id` → `users.id`, `post_id` → `forum_posts.id` |
| mod_log | Moderation audit log | `id`, `moderator_id`, `action`, `target_type`, `target_id`, `timestamp` | `moderator_id` → `users.id` |
| bad_words | Terms blocked by filter | `id`, `word` | – |

