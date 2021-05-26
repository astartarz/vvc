<?php

namespace Drupal\cdu_map\Controller;

use Drupal\Core\Url;
// Change following https://www.drupal.org/node/2457593
// See https://www.drupal.org/node/2549395 for deprecate methods information
// use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
// use Html instead SAfeMarkup

/**
 * Controller routines for Lorem ipsum pages.
 */
class KWALLMapController {

  /**
   * Constructs Lorem ipsum text with arguments.
   * This callback is mapped to the path
   * 'loremipsum/generate/{paragraphs}/{phrases}'.
   *
   * @param string $paragraphs
   *   The amount of paragraphs that need to be generated.
   * @param string $phrases
   *   The maximum amount of phrases that can be generated inside a paragraph.
   */
  public function generate($paragraphs, $phrases) {

    // Default settings.
    $config = \Drupal::config('kwall_map.settings');
    // Page title and source text.
    $page_title = $config->get('kwall_map.page_title');
    $source_text = $config->get('kwall_map.source_text');

    //$element['#title'] = SafeMarkup::checkPlain($page_title);
    $element['#title'] = Html::escape($page_title);

    // Theme function.
    $element['#theme'] = 'kwall_map';

    return $element;
  }

}