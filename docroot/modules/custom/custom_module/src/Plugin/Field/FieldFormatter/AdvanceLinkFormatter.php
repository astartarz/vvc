<?php

namespace Drupal\custom_module\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Random;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "link_advance",
 *   label = @Translation("Advance Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class AdvanceLinkFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);
    if (is_array($element)) {
      foreach ($element as $delta => $ele) {

        $random = new Random();
        $string = $random->name(5, TRUE);

        // Adding class.
        if (!empty($ele['#options']['data_video_link'])) {

          // Set data attribute.
          $element[$delta]['#attributes']['data-src'] = (strpos($ele['#options']['data_video_link'], 'youtube') ? $ele['#options']['data_video_link'] . '/?enablejsapi=1' : $ele['#options']['data_video_link']);
          $element[$delta]['#attributes']['data-toggle'] = ['al_' . $string];

          // Add play button.
          if (!empty($ele['#options']['class'])) {
            $element[$delta]['#attributes']['class'] = ['advanced-link play ' . $ele['#options']['class']];
          }
          else {
            $element[$delta]['#attributes']['class'] = ['advanced-link play'];
          }
        }
        elseif (!empty($ele['#options']['class'])) {
          $element[$delta]['#attributes']['class'] = ['advanced-link ' . $ele['#options']['class']];
        }

        // Adding aria label.
        if (!empty($ele['#options']['aria_label'])) {
          $element[$delta]['#attributes']['aria-label'] = $ele['#options']['aria_label'];
        }
      }
    }
    return $element;
  }
}

