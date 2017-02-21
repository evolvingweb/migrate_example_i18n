# Import data

The _c11n_element_ migrations import the CSV files found in this directory.
Since this module could be installed in many potential places, a special hook
is required to allow the migrations to find these files. See `c11n_migrate_i18n_migration_plugins_alter()` in `c11n_migrate_i18n.module`.
