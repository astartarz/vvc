<?php

namespace Drupal\Tests\views_node_access_filter\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views_node_access_filter\Plugin\views\filter\Editable;

/**
 * Test file.
 *
 * @group views_node_access_filter
 */
class EditableTest extends UnitTestCase {

  /**
   * The views filter plugin.
   *
   * @var \Drupal\views_node_access_filter\Plugin\views\filter\Editable
   */
  private $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new Editable([], 'foo', []);
  }

  /**
   * Ensure the plugin has no query.
   *
   * @expectedException \Exception
   */
  public function testQuery() {
    $this->assertNull($this->plugin->query());
  }

  /**
   * Ensure user cache context.
   */
  public function testGetCacheContexts() {
    $this->assertContains('user', $this->plugin->getCacheContexts());
  }

  /**
   * Ensure the filter cannot be exposed.
   */
  public function testCanExpose() {
    $this->assertFalse($this->plugin->canExpose());
  }

}
