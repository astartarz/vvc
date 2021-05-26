<?php

namespace Drupal\custom_module\Controller;

use Drupal\Core\Controller\ControllerBase;

class CustomModuleSettings extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
