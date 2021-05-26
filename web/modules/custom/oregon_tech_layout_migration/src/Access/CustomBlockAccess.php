<?php

namespace Drupal\oregon_tech_layout_migration\Access;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Accessible class to allow access.
 */
class CustomBlockAccess implements AccessibleInterface {

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
