<?php

namespace Drupal\c11n_migrate_i18n\Plugin\migrate\source;

use \Drupal\Core\Database\Query\SelectInterface;
use \Drupal\node\Plugin\migrate\source\d7\Node;
use \Drupal\migrate\Row;

/**
 * Drupal 7 node (article) source from database.
 *
 * @MigrateSource(
 *   id = "d7_node_entity_translation"
 * )
 */
class D7_Node_Entity_Translation extends Node {

  public function query() {

    $query = $this->select('entity_translation', 'et')
      // We will use certain fields from the 'entity_translation'
      // table to override certain fields from the 'node' table.
      ->fields('et', array(
        'language',
        'uid',
        'status',
        'translate',
        'created',
        'changed'
      ))
      // Ignore all but 'node' translations.
      ->condition('entity_type', 'node')
      // If we only want to consider only published translations,
      // we can set the 'status' to '1'.
      // ->condition('status', 1)
    ;

    if (empty($this->configuration['translations'])) {
      $query->condition('et.source', '');
    }
    else {
      $query->condition('et.source', '', '<>');
    }

    $query->addField('et', 'revision_id', 'vid');
    $query->addField('et', 'uid', 'revision_uid');
    $query->addField('et', 'changed', 'timestamp');

    // Join other node data in its last revision.
    $query->innerJoin('node', 'n', 'n.nid = et.entity_id');

    // Include node fields which we couldn't get from the
    // 'entity_translation' table.
    $query->fields('n', array(
      'nid',
      'type',
      'title',
      'comment',
      'promote',
      'sticky',
      'translate',
    ));
    $query->addField('n', 'uid', 'node_uid');

    if (isset($this->configuration['node_type'])) {
      $query->condition('n.type', $this->configuration['node_type']);
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
