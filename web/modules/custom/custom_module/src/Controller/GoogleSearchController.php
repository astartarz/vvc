<?php

namespace Drupal\custom_module\Controller;

use Drupal\Core\Controller\ControllerBase;

class GoogleSearchController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function content() {

    //$keyword = \Drupal::request()->get('q');
    return [
      '#type' => 'inline_template',
      '#template' => '<gcse:searchbox-only resultsurl="/search-results" queryparametername="SearchText"></gcse:searchbox-only>
<gcse:searchresults-only queryparametername="SearchText"></gcse:searchresults-only>',
      '#context' => [
      ],
    ];
  }

}
