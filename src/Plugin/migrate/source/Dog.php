<?php

namespace Drupal\c11n_migrate_i18n\Plugin\migrate\source;

use \Drupal\Core\Database\Query\SelectInterface;
use \Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 node (article) source from database.
 *
 * @MigrateSource(
 *   id = "d7_dog"
 * )
 */
class Dog extends Node {

  /**
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
   * I this method from from the Drupal 6 node source class
   * \Drupal\node\Plugin\migrate\source\d7\Node. Once the
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
