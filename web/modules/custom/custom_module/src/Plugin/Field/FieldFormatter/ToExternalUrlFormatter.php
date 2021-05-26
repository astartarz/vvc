<?php

namespace Drupal\custom_module\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'to_external_url' formatter.
 *
 * @FieldFormatter(
 *   id = "to_external_url",
 *   label = @Translation("To External Url"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ToExternalUrlFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $element = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $entity = $item->get('entity')->getTarget()->getEntity();
      if($entity->hasField('field_link') && !$entity->get('field_link')->isEmpty()){
        $element[$delta]['#url'] = Url::fromUri($entity->get('field_link')->uri);
      }
    }

    return $element;
  }

}
