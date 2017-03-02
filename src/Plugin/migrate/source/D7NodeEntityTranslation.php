<?php

namespace Drupal\migrate_example_i18n\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use \Drupal\node\Plugin\migrate\source\d7\Node as D7Node;
use \Drupal\migrate\Row;

/**
 * Drupal 7 node migrate source.
 *
 * Source plugin for reading Drupal 7 nodes translated with the
 * 'entity_translation' module.
 *
 * Use of the 'title' module for translated titles in D7 works too.
 *
 * @MigrateSource(
 *   id = "d7_node_entity_translation"
 * )
 */
class D7NodeEntityTranslation extends D7Node {

  /**
   * The D7 translation value indicating entity translation.
   */
  const ENTITY_TRANSLATION_ENABLED = 4;

  /**
   * Check if this bundle is entity-translatable.
   *
   * @return bool
   *   Whether the bundle uses entity translation.
   */
  protected function isEntityTranslatable() {
    // Cannot determine this without entity bundle.
    if (!isset($this->configuration['node_type'])) {
      return FALSE;
    }

    $variable = 'language_content_type_' . $this->configuration['node_type'];
    $translation = $this->variableGet($variable, 0);
    return $translation == self::ENTITY_TRANSLATION_ENABLED;
  }

  /**
   * Build a query which finds D7 nodes, one per row.
   *
   * The query is used to read items during the migration, and to count
   * items for the migration status.
   *
   * Since D7 Entity Translation works very differently from Content
   * Translation, we have to modify the query quite significantly.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The soure query.
   */
  public function query() {
    // Start with the parent query and see if entity_translations
    // are enabled for the nodes to be migrated.
    $query = parent::query();
    if (!$this->isEntityTranslatable()) {
      return $query;
    }

    // Entity Translation data is kept in the entity_translation table.
    $query->join('entity_translation', 'et',
      "et.entity_type = :entity_type AND et.entity_id = n.nid",
      [':entity_type' => 'node']
    );

    // Use only originals, or only translations, depending on our configuration.
    $operator = empty($this->configuration['translations']) ? '=' : '<>';
    $query->condition('et.source', '', $operator);

    // A list of fields to override from the 'entity_translation' table.
    $override = [
      'language' => 'language',
      'uid' => 'uid',
      'status' => 'status',
      'translate' => 'translate',
      'created' => 'created',
      'changed' => 'changed',
      'vid' => 'revision_id',
    ];
    $fields =& $query->getFields();
    foreach ($override as $alias => $et_column) {
      unset($fields[$alias]);
      $query->addField('et', $et_column, $alias);
    }

    return $query;
  }

  /**
   * Adds additional data to the row.
   *
   * Overridden to pass the $language argument to getFieldValues().
   *
   * @param \Drupal\Migrate\Row $row
   *   The row object.
   *
   * @return bool
   *   FALSE if this row needs to be skipped.
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

    return FieldableEntity::prepareRow($row);
  }

  /**
   * Retrieves field values for a single field of a single entity.
   *
   * Overridden to support the $language argument, so that we can retrieve
   * values for a specific languages.
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
   *   (optional) The language code.
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

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = parent::getIds();
    if (!empty($this->configuration['translations'])
      && $this->isEntityTranslatable()
    ) {
      // With Entity Translation, each translation has the same node ID.
      // To uniquely identify a row, we therefore need both nid and
      // language.
      $ids['language'] = [
        'type' => 'string',
        'alias' => 'et',
      ];
    }
    return $ids;
  }

}
