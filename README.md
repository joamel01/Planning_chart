# Work Planner

Standalone PHP 8.3/MariaDB weekly planning board.

## Roles

- `central_admin`: creates groups, creates group admins/users, archives users and their board data, resets passwords.
- `group_admin`: creates users in their own group, hides/shows users on the board, sorts board rows.
- `user`: changes their own password and edits all board cells in their group.

## Installation

1. Import `db/schema.sql` in PhpMyAdmin.
2. Configure database access with environment variables or copy `config.local.example.php` to `config.local.php`:
   - `PLANNER_DB_HOST`
   - `PLANNER_DB_NAME`
   - `PLANNER_DB_USER`
   - `PLANNER_DB_PASS`
3. Upload the `planner-en` folder to the target site.
4. Open `/planner-en/setup_admin.php` and create the first central admin.
5. Remove `setup_admin.php` after installation.

## Run With Docker

Start the application and database:

```sh
docker compose up -d --build
```

Open:

```text
http://localhost:8080/setup_admin.php
```

Create the first central admin, then remove `setup_admin.php` before publishing the
container image or deploying publicly.

Stop the stack:

```sh
docker compose down
```

Remove the local database volume:

```sh
docker compose down -v
```

## Recovery

1. Set a temporary long value in `PLANNER_RECOVERY_KEY` in `config.php`.
2. Open `/planner-en/admin_recovery.php?key=YOUR-KEY`.
3. Reset the central admin password.
4. Remove `admin_recovery.php` and clear `PLANNER_RECOVERY_KEY`.

## Board Behavior

- The board shows one week at a time. Each group can use either a 5 day week
  (Monday to Friday) or a 7 day week (Monday to Sunday).
- Cells autosave and allow any characters, maximum three characters.
- Everyone in a group can edit all visible rows in that group.
- Hidden users do not appear on the board but keep their login and saved data.
- Archived users cannot log in and their board data is marked archived.
- PDF output uses the browser print/save-as-PDF dialog.

## Suggested Code Conventions

The board intentionally allows any code up to three characters, so each
organization can use its own conventions. A simple starting point could be:

- `J`: working from home.
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
