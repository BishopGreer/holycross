# Holy Cross Parish CMS

A small PHP/MariaDB website with:

- Public editable pages
- Admin login
- Create, edit, publish, draft, and delete pages
- Web installer
- Web updater
- Automatic database migrations for install and update
- Holy Cross Parish and Friary theme assets and brand palette
- Public contact form with branded HTML email copies and SMTP support
- Admin users page with current-user list and admin user creation
- GitHub update checks and release ZIP installs from the admin updater

Current version: `1.7.2`

## Versioning

- Start project changes at `1.0.1`.
- Every update should bump `CMS_VERSION` in `core/bootstrap.php`.
- Every update that changes `CMS_VERSION` should be committed and pushed to GitHub.
- Every versioned commit should include generated release files from `tools/build-release.sh`.
- Routine fixes and small theme/content changes increase the patch version, such as `1.0.1` to `1.0.2`.
- Major feature additions or major code changes increase the minor version, such as `1.0.1` to `1.1.0`.
- Reserve major version changes, such as `2.0.0`, for breaking changes or substantial architecture changes.

## Release Files

GitHub creates downloadable release assets when a version tag is pushed:

```sh
git tag vVERSION
git push origin vVERSION
```

The release workflow checks that the tag version matches `CMS_VERSION`, builds the release ZIP, publishes a GitHub Release, and uploads the ZIP plus checksum.

For local release files, run this from the project root after changing `CMS_VERSION`:

```sh
tools/build-release.sh
```

The script creates:

- `releases/holycross-cms-VERSION.zip`
- `releases/holycross-cms-VERSION.zip.sha256`

Release archives exclude `.git`, local generated config, logs, `.DS_Store`, and previous release bundles.

## Changelog

### 1.7.2

- Fixed admin access before database migrations run by making authentication avoid newly-added user columns.

### 1.7.1

- Added a GitHub Actions release workflow that creates GitHub Releases with ZIP and checksum assets from version tags.
- Updated release packaging to exclude GitHub workflow files from installable ZIP archives.

### 1.7.0

- Added GitHub update checking from the admin updater.
- Added GitHub release ZIP installation that preserves local config and runs database migrations.

### 1.6.1

- Added release package generation.
- Updated the GitHub workflow so every version bump is committed and pushed.

### 1.6.0

- Added a display name field for admin users.
- Added user editing for display name, email address, and optional password changes.
- Added a database migration for the new user display name column.

### 1.5.0

- Added an Admin Users page that displays current users.
- Moved admin user creation from Settings to the Users page.

### 1.4.1

- Fixed slug links on the Pages admin list to use the front controller route instead of rewrite-dependent pretty URLs.

### 1.4.0

- Added an Add Admin User form to Admin Settings.
- Added validation for unique admin usernames and email addresses.

### 1.3.0

- Added a configurable mailer with native PHP mail and authenticated SMTP options.
- Expanded Admin Settings with From address, SMTP host, port, encryption, username, and password fields.
- Improved contact form send errors so mail configuration problems are visible.
- Added a settings-page test email action for verifying contact form mail delivery.

### 1.2.0

- Added a public contact page with contact name, email address, subject, and comments fields.
- Added branded HTML emails for both the configured recipient and the person submitting the form.
- Added an admin settings page for configuring the contact form recipient email address.

### 1.1.2

- Changed public menu page links to use the front controller route so pages work even when pretty URL rewrites are unavailable.
- Fixed editor toolbar commands so they preserve the current textarea selection instead of jumping to the end.

### 1.1.1

- Removed the circular logo from the public main menu.

### 1.1.0

- Added a content editor toolbar for page editing with heading, paragraph, bold, italic, quote, link, list, divider, and clear-formatting tools.

### 1.0.1

- Moved the public Admin link from the header navigation to the footer.
- Added a public footer with the Holy Cross Parish and Friary 2026 copyright.
- Documented the project versioning rule.

## Requirements

- PHP 8.1+
- MariaDB 10.4+ or MySQL 8+
- PDO MySQL extension enabled
- Web server pointed at this project directory

## Quick Start

1. Create an empty MariaDB database.
2. Make `config/` writable by PHP during installation.
3. Visit `/install/` in your browser.
4. Enter database credentials and create the first admin account.
5. Delete or restrict `/install/` after installation.

## Admin

Visit `/admin/` and sign in with the account created by the installer.

Use `/admin/settings.php` to configure the recipient email address and mail delivery settings for the public contact form. SMTP is recommended when the hosting provider does not support PHP `mail()` or requires authenticated email delivery.

## Updates

When new migration files are added to `migrations/`, or when you want to check GitHub for a newer CMS release, sign in as an admin and visit:

```text
/admin/update.php
```

The updater can install a generated release ZIP from GitHub and applies pending SQL migrations recorded in the `schema_migrations` table.

## Theme

The theme uses the supplied Holy Cross header and logo from `assets/images/`, with the style guide palette:

- Religion Red: `#BE202E`
- Franciscan Tan: `#C2B59B`
- Beard Brown: `#603A17`
- Altar White: `#FFFFFF`

## Rewrites

For Apache, `.htaccess` is included. For Nginx, route requests to `index.php` when no static file exists.
