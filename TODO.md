# TODO

This list collects future improvements for Work Planner. Items are grouped by
priority so they can be discussed, selected, and implemented gradually.

## Priority 1

- [x] Add installation safety checks.
  - Show a clear warning if `setup_admin.php` still exists after the first
    central admin has been created.
  - Show a persistent central admin banner until the file is removed.

- [x] Add database version and migration handling.
  - Add a table such as `planner_schema_versions`.
  - Track which migration files have been applied.
  - Provide a simple update page or documented update command for existing
    installations.

- [x] Add backup and export guidance.
  - Document how to back up the database for normal web server installations.
  - Document how to back up and restore the Docker database volume.
  - Consider adding CSV export for central admins.

## Priority 2

- [x] Improve mobile ergonomics.
  - Tested with real phone screenshots in portrait and landscape orientation.
  - Adjusted tap target sizes, mobile spacing, and board navigation layout.
  - Improved horizontal scrolling and sticky first column behavior for the
    planning board.

- [ ] Add an optional code legend.
  - Keep cell input free-form with a maximum of three characters.
  - Add a non-enforced code legend that can be shown near the board.
  - Consider allowing central admins or group admins to edit the legend per
    group.

- [ ] Improve save conflict visibility.
  - Current behavior is "latest save wins".
  - Consider storing and checking `updated_at` per cell during autosave.
  - Consider showing "changed by" or "last updated" information when useful.

## Priority 3

- [ ] Add automated tests.
  - Cover login and role access.
  - Cover group isolation.
  - Cover 5 day and 7 day groups.
  - Cover board autosave validation.
  - Cover hidden and archived users.

- [x] Add data export features.
  - Export a group week as CSV.
  - Export users and archived users for central admins.
  - Keep exports free of sensitive password data.

- [ ] Review public release hardening.
  - Check default configuration values.
  - Confirm no development-only files are required in production.
  - Review security headers and hosting assumptions.
