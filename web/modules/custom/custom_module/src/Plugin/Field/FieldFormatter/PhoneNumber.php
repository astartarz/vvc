<?php

namespace Drupal\custom_module\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "phone_number_formatter",
 *   label = @Translation("Phone Number"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class PhoneNumber extends LinkFormatter {


  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays Phone Number in (555)555.5555');
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      if ($item->value) {
        if(strlen($item->value) == 9) {
          preg_match('/^(\d{3})(\d{3})(\d{3})$/', $item->value, $matches);
          if($matches && count($matches) == 4) {
            $phNum = '(' . $matches[1] . ') ' . $matches[2] . '-' .$matches[3];
            $element[$delta] = ['#markup' => $phNum];
          }
        }else {
          $element[$delta] = ['#markup' => $item->value];
        }
      }
    }

    return $element;
  }
}
