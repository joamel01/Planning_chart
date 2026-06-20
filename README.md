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

## Recovery

1. Set a temporary long value in `PLANNER_RECOVERY_KEY` in `config.php`.
2. Open `/planner-en/admin_recovery.php?key=YOUR-KEY`.
3. Reset the central admin password.
4. Remove `admin_recovery.php` and clear `PLANNER_RECOVERY_KEY`.

## Board Behavior

- The board shows one work week at a time, Monday to Friday.
- Cells autosave and allow any characters, maximum three characters.
- Everyone in a group can edit all visible rows in that group.
- Hidden users do not appear on the board but keep their login and saved data.
- Archived users cannot log in and their board data is marked archived.
- PDF output uses the browser print/save-as-PDF dialog.

## Security

- Passwords are stored with `password_hash`.
- Database access uses PDO prepared statements.
- Forms and autosave requests use CSRF tokens.
- Session cookies are scoped to `PLANNER_BASE_PATH`.
