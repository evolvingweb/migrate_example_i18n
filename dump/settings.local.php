<?php

/**
 * @file
 * Database settings example.
 *
 * This is how I had my database settings configured.
 * For each Drupal installation I wanted to work with,
 * I added a separate entry to $databases. I worked with
 * Drupal 6 and two installations of Drupal 7. Hence,
 * I have three additional entries in $databases apart
 * from the 'default' entry.
 *
 * Depending on the index of $databases you use for your
 * settings, you will have to configure the 'source'
 * parameter of your migration configuration so that Drupal
 * reads the source data from the correct place.
 */

// Primary database: Drupal 8
$databases['default']['default'] = array (
  'database' => 'sandbox_d8',
  'username' => 'root',
  'password' => 'f00b@r',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

// Migration database: Drupal 6
$databases['drupal_6']['default'] = array(
  'database' => 'sandbox_d6',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
  'username' => 'root',
  'password' => 'f00b@r',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3306',
);

// Migration database: Drupal 7
// Uses 'content_translation' and 'i18n' modules for i18n.
$databases['drupal_7_content']['default'] = array(
  'database' => 'sandbox_d7_content',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
  'username' => 'root',
  'password' => 'f00b@r',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3306',
);

// Migration database: Drupal 7
// Uses 'entity_translation' and 'title' modules for i18n.
$databases['drupal_7_entity']['default'] = array(
  'database' => 'sandbox_d7_entity',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
  'username' => 'root',
  'password' => 'f00b@r',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3306',
);

// Other parameters.
$settings['install_profile'] = 'standard';
$config_directories['sync'] = 'sites/sandbox.com/files/config_42DEULKiHpKmh-k1rSl9xFOg-DlL7hEO1kjqWcTlpIA1fI4OnSmVP45zmPp-T_pnZUi67Mq5MQ/sync';
