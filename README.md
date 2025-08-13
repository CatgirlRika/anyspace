# AnySpace 
AnySpace is an Open Source Social Network platform similar to MySpace circa 2005-2007, designed with self-hosting in mind. A homage to the golden era of social networking, bringing back the simplicity and charm of early social media platforms with a focus on privacy, user experience, and community 

Designed to be lightweight, user-friendly, and customizable, allowing users to express themselves just like in the old days but with the peace of mind that modern security practices bring.

- **Profiles:** Customizable user profiles with options for background images, music, and integrated layout support.
- **Blogging:** A blogging platform for users to share thoughts, stories, and updates.
- **Messaging:** Private and secure messaging between users.
- **Friends:** Connect with others, manage friendship requests, and explore user profiles.
- **Groups:** Create and join interest-based groups for discussions and events.
- **Customization:** Extensive customization options for user profiles and blogs.

## Prerequisites
- PHP >= 5.3
- MySQL >= 5.0 or compatible database
- Web Server (Apache/Nginx)

## Install

1. Clone repo and transfer files to webserver. Webserver should serve files in `public` directory. (Example Apache and Nginx configurations are provided in the repo)
2. Navigate to you webserver to create config.php and setup the database. The `core` directory will need to be writable by the webserver user.

`pfp` and `music` folders need r/w/x permissions for the webserver user. 

It's recommended to set the following in your `php.ini`

```
file_uploads = On
upload_max_filesize = 10M
post_max_size = 15M
max_execution_time = 60
max_input_time = 120
memory_limit = 128M
```

### Admin Panel
The admin panel should not be made available to the public. The id of the admin user can be set in `config.php`, by
default it is set to user with id 1. Future plans include multi-user access to the admin panel using a permissions
system.  

## Features

- [x] Admin Panel
- [x] Authentication
  - [x] Login/Logout
  - [x] Registration
  - [x] Magic Login (passwordless login via email)
  - [ ] Email Verification
- [x] Blog
- [x] Bulletins
- [x] Comment System
- [x] Favorite Users
- [x] **Forum (Production Ready)**
  - [x] Categories and Forums
  - [x] Topics and Posts
  - [x] Rate Limiting & Anti-Spam
  - [x] Moderation Tools
  - [x] User Permissions
  - [x] Search Functionality
  - [x] Notifications & Subscriptions
  - [x] File Attachments
  - [x] Content Filtering
  - [x] Audit Logging
  - [x] Statistics Caching
- [x] Friend System
- [x] Group System
- [x] Layout sharing feature
- [x] Private Messaging
- [x] Report System
- [x] Session Management
- [x] User Browser
- [x] User Search
- [x] User Profiles
- [x] Custom HTML/CSS Profile Layouts

### Forum Module

The built-in forum system is production-ready and supports categories, topics, moderation tools, notifications, file attachments, and more. For detailed setup and usage instructions, see [docs/forum/README.md](docs/forum/README.md).

## Screenshot

![screenshot](public/docs/screenshot.png)

## Project Structure

```
project-root/
    │
├───admin/                    # Administration tools and dashboards
│
├───core/                     # Core application logic
│   ├───components/           # Shared site components
│   ├───site/                 # Site-specific functionality
│   └───tools/                # Tools and utilities
│
├───lib/                      # Libraries and dependencies
│
└───public/                   # Publicly accessible files
    │
    ├───blog/                 # Blog related files
    │   └───editor/           # Trumbowyg WYSIWIG editor components
    │       ├───langs/        # Language files for Trumbowyg
    │       └───plugins/      # Plugins for Trumbowyg 
    │
    ├───bulletins/             # Bulletins related files
    ├───docs/                  # Documentation files
    ├───forum/                 # Forum related files
    ├───groups/                # Groups related files
    ├───layouts/               # Layout related files
    ├───media/                 # User uploaded media files
    │   ├───music/             # Music files
    │   └───pfp/               # Profile picture files
    │
        └───static/                # Static assets
        ├───css/               # CSS files
        ├───icons/             # Icon files
        └───img/               # Image files

```

## Tests

The `tests` directory contains standalone PHP scripts that exercise key forum
features. To run the full test suite, execute the following from the project
root:

```bash
for t in tests/*.php; do php "$t"; done
```

Each script uses an SQLite database and prints its status to the console.

## Quirks
- Developed with PHP 5.3 compatibility in mind due to limitations of developer hardware
- Database schema will change frequently at this stage of development. If you receive a "PDO exception" you most likely need to create the appropriate  table or column.

## Credits

[MySpace](myspace.com) <br>
[SpaceHey](spacehey.com) <br>
[This spacemy.xyz codebase](https://github.com/Ahe4d/spacemy.xyz) <br>
[Trumbowyg](https://github.com/Alex-D/Trumbowyg)<br>
[@wittenbrock](https://github.com/wittenbrock/toms-myspace-page) 