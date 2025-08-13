# AnySpace Installation & Hosting Guide

## Installation
1. Upload the repository to your server and point the web server's document root to the `public/` directory.
2. Create a MySQL database and import `schema.sql`.
3. Copy `core/config.example.php` to `core/config.php` and adjust database credentials and site settings.
4. Ensure the web server user has write permission to `core/`, `public/media/pfp/`, and `public/media/music/`.
5. Visit your site to finish setup and create the first administrator account.

## Hosting Recommendations
AnySpace runs on standard PHP/MySQL hosting. Below are suggested providers ordered from budget friendly to more pricey with notes on why you might choose each.

### Shared Hosting
- **Namecheap Stellar** – low monthly cost and supports PHP & MySQL, ideal for experimenting or small friend groups.
- **Bluehost Basic** – a bit more expensive but offers better support and one‑click SSL certificates for small communities.
- **SiteGround StartUp** – higher price but strong performance and security features, useful when traffic begins to grow.

### VPS Hosting
- **DigitalOcean Droplet (Basic)** – inexpensive entry VPS giving you full server control for custom configurations.
- **Linode 4GB** – more RAM/CPU and reliable network; good for medium communities or multiple services.
- **Amazon Lightsail** – priciest here but integrates with the AWS ecosystem and offers scalability for large deployments.

Choose a provider that matches your budget and expected community size. Shared hosting is easy to manage but has limited resources, while VPS hosting requires server administration skills in exchange for greater flexibility and performance.
