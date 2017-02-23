# The site/instance to prepare.
SITE=default

# Prepare Drupal 6 installation with "content_translation"
d6:
	drush use $(SITE)
	drush dl i18n cck toolbar views wysiwyg -y
	drush en content_translation cck toolbar views wysiwyg -y
	drush cc all
	exit;

# Prepare Drupal 7 installation with "content_translation"
d7_content_tran:
	drush use $(SITE)
	drush dl i18n views wysiwyg -y
	drush cc all
	exit;

# Prepare Drupal 7 installation with "entity_translation"
d7_entity_tran:
	drush use $(SITE)
	drush dl entity_translation title views wysiwyg -y
	drush cc all
	exit;

# Prepare Drupal 8 installation for running this module.
d8:
	drush use $(SITE)
	drush dl migrate_plus migrate_tools migrate_source_csv -y
	drush en c11n_migrate_i18n -y
	drush cr
	exit;
