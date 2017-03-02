# Import data

The _example\_element_ migrations import the CSV files found in this directory. Since this module could be installed in many potential places, a special hook is required to allow the migrations to find these files. See `migrate_example_i18n_migration_plugins_alter()` in `migrate_example_i18n.module`.
