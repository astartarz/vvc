<?php

namespace Drupal\reference_table_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\reference_table_formatter\EntityToTableRenderer;
use Drupal\reference_table_formatter\FormatterBase;

/**
 * A field formatter to display a table.
 *
 * @FieldFormatter(
 *   id = "field_collection_table",
 *   label = @Translation("Table of Fields"),
 *   field_types = {
 *     "field_collection"
 *   },
 *   no_ui = true,
 * )
 *
 * @deprecated in reference_table_formatter:8.x-1.0 and is removed from
 *   reference_table_formatter:2.0.0. Use paragraphs instead of field collection
 *   in Drupal 8+.
 *
 * @see https://www.drupal.org/node/3157846
 */
class FieldCollection extends FormatterBase {

  /**
   * Constructs a new FieldCollection.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\reference_table_formatter\EntityToTableRenderer $reference_renderer
   *   The entity-to-table renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityToTableRenderer $reference_renderer, EntityTypeManagerInterface $entity_manager, EntityDisplayRepositoryInterface $display_repository) {
    @trigger_error(__CLASS__ . ' is deprecated in reference_table_formatter:8.x-1.0 and is removed from reference_table_formatter:2.0.0. Use paragraphs instead of field collection in Drupal 8+. See https://www.drupal.org/node/3157846', E_USER_DEPRECATED);

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $reference_renderer, $entity_manager, $display_repository);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIdFromFieldItem(FieldItemInterface $item) {
    $value = $item->getValue();
    return isset($value['target_id']) ? $value['target_id'] : $value['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundleId(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityId(FieldDefinitionInterface $field_definition) {
    return 'field_collection_item';
  }

}
