<?php

namespace Drupal\c11n_migrate_i18n\Plugin\migrate\source;

use \Drupal\Core\Database\Query\SelectInterface;
use \Drupal\migrate\Row;
use \Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 node (article) source from database.
 *
 * We will use this source plugin for reading Drupal 7
 * nodes translated with the 'content_translation' module.
 *
 * @MigrateSource(
 *   id = "d7_node_content_translation"
 * )
 */
class D7_Node_Content_Translation extends Node {

  /**
   * This method is responsible for generating a query
   * which would eventually be used for discovering items
   * in the D7 install. The query is used for reading items
   * during the migration and also for displaying counts
   * in migration status.
   *
   * We override this method so that we can add support
   * for the "source/translations" parameter by calling
   * the method self::handleTranslations().
   */
  public function query() {
    $query = parent::query();
    $this->handleTranslations($query);
    return $query;
  }

  /**
   * Adapt our query for translations.
   *
   * I copied this method from from the Drupal 6 node source
   * \Drupal\node\Plugin\migrate\source\d6\Node. Once the
   * "translations" parameter is supported in Drupal 7, we
   * would safely be able to remove this method.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The generated query.
   */
  protected function handleTranslations(SelectInterface $query) {
    // Check whether or not we want translations.
    if (empty($this->configuration['translations'])) {
      // No translations: Yield untranslated nodes, or default translations.
      $query->where('n.tnid = 0 OR n.tnid = n.nid');
    }
    else {
      // Translations: Yield only non-default translations.
      $query->where('n.tnid <> 0 AND n.tnid <> n.nid');
    }
  }

}
