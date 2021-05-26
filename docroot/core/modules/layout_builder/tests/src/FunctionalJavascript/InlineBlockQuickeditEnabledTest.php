<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

/**
 * Tests inline block with a user that has access to Quickedit functionality.
 *
 * @group layout_builder
 */
class InlineBlockQuickeditEnabledTest extends InlineBlockTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['quickedit'];

  /**
   * {@inheritdoc}
   */
  protected function drupalCreateUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) {
    $permissions[] = 'access in-place editing';
    return parent::drupalCreateUser($permissions, $name, $admin, $values);
  }
}
