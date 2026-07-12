# Work Planner

Standalone PHP 8.3/MariaDB weekly planning board.

## Roles

- `central_admin`: creates groups, creates group admins/users, archives users and their board data, resets passwords.
- `group_admin`: creates users in their own group, hides/shows users on the board, sorts board rows.
- `user`: changes their own password and edits all board cells in their group.

## Requirements

For a normal web server installation:

- PHP 8.3 or newer with PDO MySQL support.
- MariaDB or MySQL.
- Access to create/import database tables, for example with PhpMyAdmin.
- A web server that can serve PHP files.

For a Docker installation:

- Docker.
- Docker Compose.

## Choose An Installation Method

Use one of these methods:

- **Normal web server installation**: use this for shared hosting, a VPS, or a
  web host such as one.com. You upload the PHP files yourself and use an
  existing MariaDB/MySQL database.
- **Docker installation**: use this for local testing, development, demos, or a
  server where you want Docker to run both the PHP application and MariaDB.

Do not mix the two methods unless you know exactly why. A normal web server
installation does not need Docker. A Docker installation does not need
PhpMyAdmin unless you want to inspect the database manually.

## Normal Web Server Installation

1. Create an empty MariaDB/MySQL database with your hosting provider.
2. Import `db/schema.sql` into that database. In PhpMyAdmin this is usually done
   with the **Import** tab.
3. Copy `config.local.example.php` to `config.local.php`.
4. Edit `config.local.php` and enter your database settings:
   - `PLANNER_DB_HOST`: database host, often `localhost`.
   - `PLANNER_DB_NAME`: database name.
   - `PLANNER_DB_USER`: database username.
   - `PLANNER_DB_PASS`: database password.
5. If the application is installed in another folder than `/planner-en`, set
   `PLANNER_BASE_PATH` in `config.local.php` or `config.php`.
6. Upload all application files to the web server folder where the app should
   run. The default path is `/planner-en`.
7. Open `/planner-en/setup_admin.php` in your browser.
8. Create the first central admin account.
9. Delete `setup_admin.php` from the public server after the first admin has
   been created. Central admins will see a warning while this file exists.
10. Log in at `/planner-en/login.php`.

After login, the central admin can create groups, choose 5 or 7 day weeks, add
group admins, add users, reset passwords, and archive users.

## Docker Installation

Use Docker when you want the included `docker-compose.yml` file to start both
the PHP application and a MariaDB database.

Start the application and database from this project folder:

```sh
docker compose up -d --build
```

Then open:

```text
http://localhost:8080/setup_admin.php
```

Create the first central admin account. After that, log in at:

```text
http://localhost:8080/login.php
```

If this Docker image will be published or used on a public server, remove
`setup_admin.php` after the first admin has been created. Central admins will
see a warning while this file exists.

The Docker setup uses these default database settings internally:

- database host: `db`
- database name: `planning_chart`
- database user: `planning_chart`
- database password: `planning_chart_password`

These values are for local testing. Change the database passwords before using
Docker on a public server.

The Docker application runs at the root path `/`, so it uses
`PLANNER_BASE_PATH=/` in `docker-compose.yml`.

Stop the Docker stack:

```sh
docker compose down
```

Remove the local Docker database volume and start with an empty database:

```sh
docker compose down -v
```

Only use `docker compose down -v` when you really want to delete the local test
database.

## Production Hardening Checklist

Before publishing a normal web server installation:

1. Use a private `config.local.php` with the real database settings.
2. Make sure `config.local.php` is not committed to Git or exposed by the web
   server.
3. Create the first central admin with `setup_admin.php`.
4. Remove `setup_admin.php` from the public server after setup.
5. Keep `PLANNER_RECOVERY_KEY` empty during normal operation.
6. Remove `admin_recovery.php` from the public server unless you are actively
   recovering a central admin account.
7. Open **Admin > Release** and resolve any warnings.
8. Confirm the site is served over HTTPS so session cookies are marked secure.

Before publishing a Docker installation:

1. Replace `planning_chart_password` and `root_password` in
   `docker-compose.yml` or provide production secrets another way.
2. Set a production-safe `PLANNER_SESSION_NAME`.
3. Keep `PLANNER_BASE_PATH=/` only when the app is served from the domain root.
4. Create the first central admin, then remove or block `setup_admin.php`.
5. Keep `PLANNER_RECOVERY_KEY` empty except during recovery.
6. Put Docker behind HTTPS, for example with a reverse proxy.
7. Open **Admin > Release** and resolve any warnings.

## Updating An Existing Installation

For a normal web server installation:

1. Back up the database before updating.
2. Upload the changed application files.
3. Log in as a central admin.
4. Open the **Updates** admin page.
5. If migration tracking has not been initialized yet, click **Initialize
   migration tracking**.
6. If pending migrations are shown later, click **Apply pending migrations**.

For Docker, pull or copy the new files and rebuild the app container:

```sh
docker compose up -d --build app
```

If a new migration was added after your Docker database volume was created, run
the update from the **Updates** admin page, or recreate the volume with
`docker compose down -v` if this is only a disposable test installation.

## Backup And Export

Use database backups when you need a restore point. CSV exports are useful for
reviewing data, but they are not a complete replacement for a database backup.

For normal web server installations:

1. Open your hosting provider's database tool, for example PhpMyAdmin.
2. Select the Work Planner database.
3. Use **Export** to download a full SQL backup.
4. Store the backup securely.

For Docker installations, create a SQL backup with:

```sh
docker compose exec -T db mariadb-dump -uplanning_chart -pplanning_chart_password planning_chart > planning-chart-backup.sql
```

Restore a Docker SQL backup into an empty database with:

```sh
docker compose exec -T db mariadb -uplanning_chart -pplanning_chart_password planning_chart < planning-chart-backup.sql
```

Central admins can also open the **Export** admin page and download CSV files
for one group week, active users, archived users, and plan entries. These CSV
files do not include password hashes.

## Recovery

1. Set a temporary long value in `PLANNER_RECOVERY_KEY` in `config.local.php`
   or `config.php`.
2. Open `/planner-en/admin_recovery.php?key=YOUR-KEY`.
3. Reset the central admin password.
4. Remove `admin_recovery.php` from the public server and clear
   `PLANNER_RECOVERY_KEY`.

## Board Behavior

- The board shows one week at a time. Each group can use either a 5 day week
  (Monday to Friday) or a 7 day week (Monday to Sunday).
- Cells autosave and allow any characters, maximum three characters.
- Everyone in a group can edit all visible rows in that group.
- Hidden users do not appear on the board but keep their login and saved data.
- Archived users cannot log in and their board data is marked archived.
- PDF output uses the browser print/save-as-PDF dialog.

## Mobile Use

The application is designed to be useful first on mobile phones, while still
supporting tablets, laptops, and desktop screens.

The board works in both portrait and landscape orientation on a phone. Landscape
orientation usually gives the best planning experience because more columns fit
on screen at the same time. Portrait orientation is still supported with
horizontal scrolling.

For the best phone experience, add the site to the phone home screen and open it
like a web app:

- iPhone/iPad Safari: open the site, tap **Share**, then **Add to Home Screen**.
- Android Chrome: open the site, tap the menu, then **Add to Home screen** or
  **Install app**.

The app includes web app metadata so it can open in standalone mode when the
browser and operating system support it.

## Suggested Code Conventions

The board intentionally allows any code up to three characters, so each
organization can use its own conventions. A simple starting point could be:

- `WFH`: working from home.
- `FLX`: flex leave.
- `OFF`: off duty.
- `PHY`: physical training.
- `AM`: morning only or morning absence.
- `PM`: afternoon only or afternoon absence.
- Three-letter location codes for working at another location.
- `X`: absent for the full day.
- `X/`: absent in the morning.
- `/X`: absent in the afternoon.

These examples are suggestions only. The application does not enforce specific
codes.

## Security

- Passwords are stored with `password_hash`.
- Database access uses PDO prepared statements.
- Forms and autosave requests use CSRF tokens.
- Session cookies are scoped to `PLANNER_BASE_PATH`.
- Central admins are warned if `setup_admin.php` still exists after
  installation.
- Central admins can use **Admin > Release** to review public deployment checks.
- The included `.htaccess` blocks common private files and folders when Apache
  honors `.htaccess`.
- The application sends browser security headers for PHP pages.
- Usernames are stored in lowercase and are case-insensitive at login; names and passwords keep their original casing.

## Languages

The application discovers language packs automatically from `locales/*.php`.
English (`locales/en.php`) and Swedish (`locales/sv.php`) are included. Users
can choose a language from the selector and the choice is saved to their
account after the `2026-07-11_add_user_locale.sql` migration has been applied.

To add a language, copy `locales/en.php`, rename the file to a valid locale
code such as `de.php`, and translate its values. The file name and its `code`
value must match. The application uses the file's `name` value in the language
selector and falls back to the default language when a translation key is
missing.

Language files are PHP files and must only be added by a trusted server
administrator. They are intentionally blocked from direct web access by
`.htaccess`; do not add language-file upload to the application.

For an existing installation, log in as a central admin and apply the pending
database migration from **Admin > Updates** before saving a language choice to
an account.

## Roadmap

Use GitHub Issues for public feature requests, bug reports, and roadmap
discussion.
