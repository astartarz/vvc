<?php

namespace Drupal\reference_table_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\reference_table_formatter\FormatterBase;

/**
 * A field formatter to display a table.
 *
 * @FieldFormatter(
 *   id = "entity_reference_table",
 *   label = @Translation("Table of Fields"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EntityReference extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityIdFromFieldItem(FieldItemInterface $item) {
    return $item->getValue()['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundleId(FieldDefinitionInterface $field_definition) {
    $definition_settings = $field_definition->getSettings();
    if (strpos($definition_settings['handler'], 'default') === 0) {
      $target_entity_type = $this->entityManager->getDefinition($this->getTargetEntityId($field_definition));

      if (!$target_entity_type->hasKey('bundle')) {
        $target_bundle = $definition_settings['target_type'];
      }
      elseif (!empty($definition_settings['handler_settings']['target_bundles'])) {
        // Default to the first bundle, currently only supporting a single
        // bundle.
        $target_bundle = array_values($definition_settings['handler_settings']['target_bundles']);
        $target_bundle = array_shift($target_bundle);
      }
      else {
        throw new \Exception('Cannot render reference table for ' . $this->fieldDefinition->getLabel() . ': target_bundles setting on the field should not be empty.');
      }
    }
    else {
      // Since we are only supporting rendering a single bundle, we wont know
      // what bundle we are rendering if users aren't using the default
      // selection, which is a simple configuration form.
      throw new \Exception('Using non-default reference handler with reference_table_formatter has not yet been implemented.');
    }
    return $target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityId(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type');
  }

}
