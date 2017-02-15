<?php

namespace Drupal\c11n_migrate_i18n\Plugin\migrate\source;

use \Drupal\Core\Database\Query\SelectInterface;
use \Drupal\node\Plugin\migrate\source\d7\Node;
use \Drupal\migrate\Row;

/**
 * Drupal 7 node (article) source from database.
 *
 * We will use this source plugin for reading Drupal 7
 * nodes translated with the 'entity_translation' and
 * 'title' modules.
 *
 * @MigrateSource(
 *   id = "d7_node_entity_translation"
 * )
 */
class D7NodeEntityTranslation extends Node {

  /**
   * This method is responsible for generating a query
   * which would eventually be used for discovering items
   * in the D7 install. The query is used for reading items
   * during the migration and also for displaying counts
   * in migration status.
   *
   * We override this method so that we can add support
   * for the "source/translations" parameter. We had to
   * write a separate source plugin for this case because
   * node translations using the 'entity_translation' module
   * works in a very differently than 'content_translation'.
   * So, we have to design a custom query such that it
   * returns the same set of fields as 'parent::query()',
   * but using the 'entity_translation' table as the base
   * table.
   */
  public function query() {

    $query = parent::query();

    // If translations are enabled, then we need to use the
    // 'entity_translation' table and read certain data from
    // there.
    //
    // Once the 'translations' parameter is supported in core,
    // the 'query' method might check for another parameter
    // like 'translation_type: entity_translation' to determine
    // whether to execute the modifications we have done below.
    if (!empty($this->configuration['translations'])) {

      $query->innerJoin('entity_translation', 'et', 'et.entity_id = n.nid AND et.entity_type = :entity_type', [
        ':entity_type' => 'node',
      ]);

      // A list of fields which we wish to override with fields
      // from the 'entity_translation' table.
      $field_override_coll = [
        'language' => 'language',
        'revision_uid' => 'uid',
        'status' => 'status',
        'translate' => 'translate',
        'created' => 'created',
        'changed' => 'changed',
        'vid' => 'revision_id',
        'tnid' => 'entity_id',
      ];

      // Remove certain fields which we would override with
      // equivalent fields in the 'entity_translation' table.
      $field_query_coll =& $query->getFields();
      foreach ($field_override_coll as $column_alias => $column_name) {
        unset($field_query_coll[$column_alias]);
        $query->addField('et', $column_name, $column_alias);
      }

      // Make sure we only read translations.
      $query->condition('et.source', '', '<>');

    }

    return $query;

  }

  /**
   * Override this method so that we can pass a 'language'
   * argument to our modified self::getFieldValues() method
   * so that we only attach field values in the correct
   * language.
   *
   * If the D7/Node migration source had support for loading
   * field values in a specific language, we would not have
   * had to override this method. This issue is marked as a
   * 'todo' for now, so we override this method.
   *
   * Basically, in this method, we load and attach field values
   * for the given base node / translation in it's language.
   */
  public function prepareRow(Row $row) {
    // Get field identifiers.
    foreach (array_keys($this->getFields('node', $row->getSourceProperty('type'))) as $field) {
      $nid = $row->getSourceProperty('nid');
      $vid = $row->getSourceProperty('vid');
      $language = $row->getSourceProperty('language');
      // Get field values.
      $row->setSourceProperty($field, $this->getFieldValues('node', $field, $nid, $vid, $language));
    }
  }

  /**
   * Retrieves field values for a single field of a single entity.
   *
   * This method has been overridden just to support the $language
   * argument. Without that, one cannot specify the language in which
   * one wants to read the fields.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field
   *   The field name.
   * @param int $entity_id
   *   The entity ID.
   * @param int|null $revision_id
   *   (optional) The entity revision ID.
   * @param string|null $language
   *   (optional) The language.
   *
   * @return array
   *   The raw field values, keyed by delta.
   */
  protected function getFieldValues($entity_type, $field, $entity_id, $revision_id = NULL, $language = NULL) {

    $table = (isset($revision_id) ? 'field_revision_' : 'field_data_') . $field;
    $query = $this->select($table, 't')
      ->fields('t')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('deleted', 0);
    if ($language) {
      $query->condition('language', $language);
    }
    if (isset($revision_id)) {
      $query->condition('revision_id', $revision_id);
    }
    $values = [];
    foreach ($query->execute() as $row) {
      foreach ($row as $key => $value) {
        $delta = $row['delta'];
        if (strpos($key, $field) === 0) {
          $column = substr($key, strlen($field) + 1);
          $values[$delta][$column] = $value;
        }
      }
    }
    return $values;
  }

}
