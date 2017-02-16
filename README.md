# Drupal 8 I18N / Translation Migration Example

Although a majority of sites only offer their content in one language, there are many which offer all or some of their content in two or more languages. When a multi-language site decides to migrate to Drupal 8, one of the major concerns is migrating the content whilst preserving the translations. Luckily, Drupal 8 has a very straight forward and standardized framework for supporting translations, unlike its predecessors.

In this project, we would briefly discuss how to migrate translated content into Drupal 8. More specifically, we would see how to migrate the following items into Drupal 8:

* Drupal 6 content - translated with the 'content_translation' module.
* Drupal 7 content - translated with the 'content_translation' module.
* Drupal 7 content - translated with the 'entity_translation' module.
* Non-drupal content - CSV files containing base data and translations.

# Quick start

* Download the files from this repo and put them in the `modules/custom/c11n_migrate_i18n` directory. `git clone https://github.com/jigarius/drupal-migration-example.git modules/custom/c11n_migrate`
* Install the module. `drush en c11n_migrate -y`
* Create source database for Drupal 6 / Drupal 7 examples and import the relevant SQL dump:
  * For Drupal 6, import [dump/sandbox_d6.sql]
  * For Drupal 7 content translations, import [dump/sandbox_d7a.sql]
  * For Drupal 7 entity translations, import [dump/sandbox_d7b.sql]
* Configure additional databases in Drupal 8 - refer to [dump/settings.local.php]
  * To avoid confusion, better to name the databases the same way I have named them. Otherwise, you might have to change certain parameters in the migration definitions.
* See current status of the migrations. `drush migrate-status`
* Run / re-run the migrations introduced by this module. `drush migrate-import MIGRATION-ID --update`. Make sure your replace `MIGRATION-ID` with the appropriate ID.

# The problem

We have 4 sets of data from various sources which we have to migrate into Drupal 8:

* **Drupal 6 - Content Translation:** A bunch of _story_ nodes about hybrid animals need to be migrated to Drupal 8. These have been handled in the `config/install/migrate_plus.migration.c11n_hybrid_*.yml` files.
* **Drupal 7 - Content Translation:** A bunch of _article_ nodes about dogs need to be migrated to Drupal 8. These have been handled in the `config/install/migrate_plus.migration.c11n_dog_*.yml` files.
* **Drupal 7 - Entity Translation:** A bunch of _article_ nodes about mythological creatures need to be migrated to Drupal 8. These have been handled in the `config/install/migrate_plus.migration.c11n_creature_*.yml` files.
* **Non-drupal source:** A table of chemical elements is provided in 2 different files - one in English and the other in Spanish. We need to migrate the contents of these two files and create nodes having translations in English and Spanish. These have been handled in the `config/install/migrate_plus.migration.c11n_element_*.yml` files.

# Assumptions

Since this is an advanced migration topic, it is assumed that you already have the following knowledge:

* How to create a custom module in Drupal 8
* How to write a basic migration in Drupal 8
* How to install and use [drush](http://www.drush.org/) commands

# The module

There is nothing special about the module definition as such, however, here are certain things which need a mention:

* In Drupal 8, unlike Drupal 7, a module only provides a .module file only if required. In our example, we do not need the .module file, so I have not created one.
* I usually prefer to name project-specific custom modules with a prefix of `c11n` (being the numeronym for _customization_). This way, we have a naming convention for custom modules and we can copy any custom module to another site without worrying about having to change prefixes. You can name your module anything though - personal preference.
* Though the migrate module is in Drupal 8 core, we need most of these dependencies to enable / enhance migrations on the site:
  * [migrate_plus](https://www.drupal.org/project/migrate_plus)
  * [migrate_tools](https://www.drupal.org/project/migrate_tools)
  * [migrate_source_csv](https://www.drupal.org/project/migrate_source_csv)
* The [c11n_migrate_i18n.install](c11n_migrate_i18n.install) is not required under normal circumstances, however, I have implemented `hook_install()` and `hook_uninstall()` in that file. See the [import/README.md](import/README.md) file for more information.
