<?php

namespace Drupal\Tests\inline_block_title_automatic\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\layout_builder\Section;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the inline block automatic module.
 *
 * @group inline_block_title_automatic
 */
class InlineBlockTitleAutomaticTest extends BrowserTestBase {

  /**
   * A test node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * A test reusable block.
   *
   * @var \Drupal\block_content\Entity\BlockContent
   */
  protected $reusableBlock;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'layout_builder',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'example',
      'label' => 'Example',
    ])->save();

    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $display */
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'example',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->enableLayoutBuilder();
    $display->setOverridable();
    $display->save();

    $this->node = Node::create([
      'type' => 'example',
      'title' => 'Foo',
    ]);
    $this->node->layout_builder__layout->appendSection(new Section('layout_onecol'));
    $this->node->save();

    BlockContentType::create([
      'id' => 'rich_text',
      'label' => 'Rich Text',
    ])->save();
    $this->reusableBlock = BlockContent::create([
      'info' => 'Reusable!',
      'type' => 'rich_text',
    ]);
    $this->reusableBlock->setReusable();
    $this->reusableBlock->save();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test the automatic title.
   */
  public function testAutomaticTitle() {
    // Before the module is installed, the title fields should be visible on
    // reusable and inline block content entities.
    $this->drupalGet("layout_builder/add/block/overrides/node.{$this->node->id()}/0/content/inline_block:rich_text");
    $this->assertTitleFieldsExist();
    $this->drupalGet("layout_builder/add/block/overrides/node.{$this->node->id()}/0/content/block_content:{$this->reusableBlock->uuid()}");
    $this->assertTitleFieldsExist();
    // Non block_content entity blocks should be unaffected.
    $this->drupalGet("layout_builder/add/block/overrides/node.{$this->node->id()}/0/content/user_login_block");
    $this->assertTitleFieldsExist();

    // After installing the module, the title fields should be hidden.
    $this->container->get('module_installer')->install(['inline_block_title_automatic']);
    $this->drupalGet("layout_builder/add/block/overrides/node.{$this->node->id()}/0/content/inline_block:rich_text");
    $this->assertTitleFieldsNotExist();
    $this->drupalGet("layout_builder/add/block/overrides/node.{$this->node->id()}/0/content/block_content:{$this->reusableBlock->uuid()}");
    $this->assertTitleFieldsNotExist();
    // However the non block_content block should be unaffected.
    $this->drupalGet("layout_builder/add/block/overrides/node.{$this->node->id()}/0/content/user_login_block");
    $this->assertTitleFieldsExist();
  }

  /**
   * Assert the title fields exist.
   */
  protected function assertTitleFieldsExist() {
    $this->assertSession()->fieldExists('settings[label]');
    $this->assertSession()->fieldExists('settings[label_display]');
  }

  /**
   * Assert the title fields do not exist.
   */
  protected function assertTitleFieldsNotExist() {
    $this->assertSession()->fieldNotExists('settings[label]');
    $this->assertSession()->fieldNotExists('settings[label_display]');
  }

}
