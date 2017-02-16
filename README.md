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
* How to configure a multi-linguage website on Drupal 8

# The module

There is nothing special about the module definition as such, however, here are certain things which need a mention:

* In Drupal 8, unlike Drupal 7, a module only provides a .module file only if required. In our example, we do not need the .module file, so I have not created one.
* I usually prefer to name project-specific custom modules with a prefix of `c11n` (being the numeronym for _customization_). This way, we have a naming convention for custom modules and we can copy any custom module to another site without worrying about having to change prefixes. You can name your module anything though - personal preference.
* Though the migrate module is in Drupal 8 core, we need most of these dependencies to enable / enhance migrations on the site:
  * [migrate_plus](https://www.drupal.org/project/migrate_plus)
  * [migrate_tools](https://www.drupal.org/project/migrate_tools)
  * [migrate_source_csv](https://www.drupal.org/project/migrate_source_csv)
  * migrate_drupal: We need this module to use Drupal 6 and Drupal 7 sites as data sources for our migration.
* The [c11n_migrate_i18n.install](c11n_migrate_i18n.install) is not required under normal circumstances, however, I have implemented `hook_install()` and `hook_uninstall()` in that file. See the [import/README.md](import/README.md) file for more information.

# Drupal 8 configuration

Before migrating translated content into Drupal 8, one must make sure that their Druapl 8 site actually supports translated content. To do this, we need to:

* Enable the `language` module and set up languages and method of language determination. Example: Set up English and French.
* Enable the `content_translation` module.
* Configure the content types which you want to be translatable. Example, edit the _Article_ content type and enable translations.
* Make sure you have your content types and fields configured as per the data you wish to import. Example, if your source articles have a field named _One-liner_, make sure the Drupal 8 nodes have a corresponding field to save the data in.

# Migrate hybrids: Drupal 6 content translations to Drupal 8

Since Drupal 6 is older, it looks like a better place to start. To get started, we create a migration group named [c11n_hybrid](config/install/migrate_plus.migration_group.c11n_hybrid.yml) (optional). This would let us execute all grouped migrations with one command like

    drush migrate-import --group=c11n_hybrid --update

Migrating translated content into Drupal 8 usually involves two steps:

* Base migration: Migrate data in base language and ignore translations.
* Translation migration: Migrate only the translations (and ignore data in base language). These translations are usually linked to the content we create in the first step, thereby leaving us with only one entity with multiple translations.

Before jumping into writing these migrations, it is important to mention that Drupal 6 and Drupal 8 translations work very differently. Here's the difference in a nut-shell:

* Drupal 6: First, you create a piece of content in it's base language. Then, you add a translation of it. However, when you create a translation, another fresh node is created with a different ID and a property named `tnid` is used to save the ID of the original node, thereby recording the fact that the node is a translation of another node. For language-neutral content the `tnid` is set to 0.
* Drupal 8: First, you create a piece of content in it's base language. Then, you add a translation of it. When you create the translation, no new node is created. The translation is saved against the original node itself but measures are taken to save the translations in the other language.

Hence we follow the two step process for migrating translated content from Drupal 6.

## Hybrid base migration

Having created the migration group, we would create our first migration with the ID [c11n_hybrid_base](config/install/migrate_plus.migration.c11n_hybrid_base.yml). We do this by defining some usual parameters:

* **id:** An unique ID for the migration.
* **migration_group:** The group to which the migration belongs.
* **migration_tags:** A set of tags for the migration.
* **source:**
  * **plugin:** Since we want to import data from a Drupal installation, we need to set the source plugin to `d6_node`. The `d6_node` source plugin is introduced by the `migrate_drupal` module and it helpss read nodes from a Drupal 6 installation without having to manually write queries to read the nodes and attaching the relevant fields, etc.
  * **node_type:** With this parameter we tell the source plugin that we are interested in a particular node type only, in this case, _story_.
  * **key:** Since we intend to read the Drupal 6 data from a secondary database connection (the primary one being the Drupal 8 database), we need to define the secondary connection in the `$databases` global variable in our `settings.local.php` file. Once done, we need to mention the `key` of the `$databases` array where the Drupal 6 connection is defined.
  * **target:** Optionally, you can also define a _target_. This parameter defaults to `default` and should be defined if your connection is not defined in the `default` sub-key of `$databases`.
  * **constants:** We define some static / hard-coded values under this parameter.
  * **translations:** We DO NOT define the translations parameter while migrating base data. Omiting the parameter or setting it to `false` tells the source plugin that we are only interested in migrating non-translations, i.e. content in base language and language-neutral content. It is important NOT to specify this parameter otherwise you will end up with separate nodes for every language variation of each node.
* **destination:**
  * **plugin:** Since we want to create node entities, we specify this as `entity:node`. That's it.
  * **translations:** We DO NOT define the translations parameter while migrating base data. Omiting the parameter or setting it to `false` tells the destination plugin that we are interested in creating fresh nodes for each record as opposed to associating them as translations for existing nodes.
* **process:** This is where we tell migrate how to map the old node properties to the new node properties. Most of the properties have been assigned as is, without alteration, however, some note-worthy properties have been discussed below:
  * **type:** We use a constant value to define the type of nodes we wish to create from the imported data.
  * **langcode:** The `langcode` parameter was formerly `language` in Drupal 6. So we need to assign it properly so that Drupal 8 knows as to in which language the node is to be created. We use the `default_value` plugin here to provide a fallback to the `und` or `undefined` language just in case some node is out of place, however, it is highly unlikely that it happens.
  * **body:** We can assign this property directly to the `body` property. However, the Drupal 6 data is treated as plain text in Drupal 8 in that case. So migrating with `body: body`, the imported nodes in Drupal 8 would show visible HTML markup on your site. To resolve this, we explicitly assign the old `body` to `body/value` and specify that the text is in HTML by writing `body/format: constants/body_format`. That tells Drupal to treat the body as _Full HTML_.

This takes care of the base data. If you run this migration with `drush migrate-import c11n_hybrid_i18n --update`, all Drupal 6 nodes which are in base language or are language-neutral will be migrated into Drupal 8.

## Hybrid translation migration

We are half-way through now and all that's missing is migrating translations of the nodes we migrated above. To do this, we create another migration with the ID [c11n_hybrid_i18n](config/install/migrate_plus.migration.c11n_hybrid_i18n.yml). The migration definition remains mostly the same but has the following important differences from the base migration:

* **source:**
  * **translations:** We define this parameter to make the source plugin read only translation nodes and to make it ignore the nodes we already migrated in the base migration.
* **destination:**
  * **translations:** We define this parameter to make the destination plugin create translations for existing nodes instead of creating fresh nodes for each source record.
* **process:**
  * **nid:** Are we defining an ID for the nodes to be generated? Yes, we are. With the `nid` parameter, we use the `migration` plugin and tell Drupal to create translations for the nodes we created during the base migration, like `plugin: migration` and `migration: c11n_hybrid_base`. So, for every record, Drupal derives the ID of the relevant node created during the base migration and creates a translation for it.
  * **langcode:** This is important because here we define the language in which the translation should be created.
* **migration_dependencies:** Since we cannot associate the translations to the base nodes if the base nodes do not exist, we tell Drupal that this migration depends on the base migration `c11n_hybrid_base`. That way, one will be forced to run the base migration before running this migration.

That's it! We can run our translation migration with `drush migrate-import c11n_hybrid_i18n --update` and the translations will be imported into Drupal 8. You can check if everything went alright by clicking the `Translate` option for any translated node in Drupal 8. If everything went correctly, you should see that the node exists in the original language and has one or more translations.

# Migrate dogs: Drupal 7 content translations to Drupal 8

Great! So another set of content translations! The good news is that content translations work the same was in Drupal 7 as they do in Drupal 6. However, at the time of writing text, we do not have good support for migrating translated content from Drupal 7 into Drupal 8. More precisely, a parameter named `translations` is not supported by the `d7_node` migration source plugin. Without the parameter, we do not have any easy method for importing only non-translated content or vice-versa. All migrations end up importing both non-translations and translations. Apart from that, everything works just like the Drupal 6 migration discussed above.

But, the code must go on! Until the `translations` parameter or an equivalent is supported out of the box, we can create a custom source plugin like [D7NodeContentTranslation](src/Plugin/migrate/src/D7NodeContentTranslation.php). Here's a quick introduction to the class:

* The class is derived from `\Drupal\node\Plugin\migrate\source\d7\Node` which would eventually support the `translations` parameter and make our lives easier.
* The annotation `@MigrateSource` makes it available as a migration source plugin. The plugin ID being `d7_node_content_translation`.
* The `query` method has been overridden to intercept the query used by the migration module to read source records. We call a `handleTranslations` method on the query which does what it's name says, handles translations.
* The `handleTranslations` method is an exact copy of the one which exists in the Drupal 6 node source plugin. It adds support for the `tranlsations` parameter:
  * If `translations: true`, then it modifies the query so that it would only return translated nodes.
  * If `translations: false`, then it modifies the query so that it would only return non-translations, i.e. nodes in base language and language-neutral nodes.

Apart from that, we have everything going just the way we did for Drupal 6.

* We define a [c11n_dog_base](config/install/migrate_plus.migration.c11n_dog_base.yml) migration.
  * We use our `d7_node_content_translation` plugin as the `source` plugin.
  * We do not declare `translations` parameter for the `source` plugin, so that only non-translations are read from Drupal 7.
  * We do not declare `translations` parameter for the `destination` plugin. Thus, separate Drupal 8 nodes will be generated for every Drupal 7 node.
* We define a [c11n_dog_i18n](config/install/migrate_plus.migration.c11n_dog_i18n.yml) migration.
  * We use our `d7_node_content_translation` plugin as the `source` plugin.
  * We define `translations: true` for the source plugin so that only translated nodes are read from Drupal 7
  * We define `translations: true` for the destination plugin so that instead of the data is migrated as translations for nodes created during the base migration.
  * We make sure that the `i18n` migration depends on the `base` migration.

That's it! We can run the base and i18n migrations one by one and all Drupal 7 nodes would be imported to Drupal 8 along with their translations. To execute both the migrations at once, we can run the command `drush migrate-import c11n_dog_i18n --update --execute-dependencies`. The `--execute-dependencies` parameter will ensure that the `base` migration runs before the `i18n` migration. Perfect!
