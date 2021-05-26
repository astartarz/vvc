<?php

namespace Drupal\Tests\reference_table_formatter\Kernel;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Render\RenderContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\reference_table_formatter\EntityToTableRenderer;

/**
 * Tests reference table formatter entity to table renderer.
 *
 * @group reference_table_formatter
 */
class EntityToTableRendererTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'reference_table_formatter'];

  /**
   * Node entities to test with.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * The display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity table renderer undertest.
   *
   * @var \Drupal\reference_table_formatter\EntityToTableRendererInterface
   */
  protected $tableRenderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installSchema('node', ['node_access']);

    NodeType::create(['type' => 't_shirt'])->save();

    $fields = [
      [
        ['type' => 'string', 'field_name' => 'field_color'],
        ['label' => 'Color'],
      ],
      [
        ['type' => 'float', 'field_name' => 'field_price'],
        ['label' => 'Price', 'settings' => ['prefix' => '$']],
      ],
      [
        ['type' => 'string', 'field_name' => 'field_size'],
        ['label' => 'Size'],
      ],
    ];

    foreach ($fields as $parameters) {
      list($storage, $instance) = $parameters;
      $this->createField($storage, $instance + ['bundle' => 't_shirt']);
    }

    EntityViewMode::create([
      'id' => 'node.teaser',
      'targetEntityType' => 'node',
    ])->save();

    $this->displayRepository = $this->container->get('entity_display.repository');

    $this->displayRepository
      ->getViewDisplay('node', 't_shirt', 'teaser')
      ->setComponent('title', ['weight' => -5])
      ->setComponent('field_color', ['weight' => 3])
      ->setComponent('field_price', ['weight' => 1])
      ->setComponent('field_size', ['weight' => 2])
      ->save();

    $node_0 = Node::create([
      'title' => 'Red Medium T',
      'type' => 't_shirt',
      'field_color' => 'Red',
      'field_price' => 1.00,
      'field_size' => 'M',
    ]);
    $node_0->save();

    $node_1 = Node::create([
      'title' => 'Green Large T',
      'type' => 't_shirt',
      'field_color' => 'Green',
      'field_price' => 2.00,
      'field_size' => 'L',
    ]);
    $node_1->save();

    $this->nodes = [$node_0, $node_1];

    $entity_manager = $this->container->get('entity_type.manager');
    $this->renderer = $this->container->get('renderer');
    $this->tableRenderer = new EntityToTableRenderer($entity_manager, $this->renderer, $this->displayRepository);
  }

  /**
   * Test standard table rendering.
   *
   * @dataProvider tableRenderingDataProvider
   */
  public function testRendering($settings, $expected_header, $expected_rows, $empty_field = FALSE) {
    if ($empty_field) {
      $this->nodes[0]->field_size = [];
      $this->nodes[0]->save();
    }

    $table = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($settings) {
      return $this->tableRenderer->getTable('node', 't_shirt', $this->nodes, $settings);
    });

    $this->assertEquals('table', $table['#theme']);
    $this->assertEquals($expected_header, $table['#header'], 'Assert table headers are correct.');

    foreach ($table['#rows'] as $i => $row) {
      foreach ($row as $j => $field) {
        $this->assertEquals($expected_rows[$i][$j], trim(strip_tags($field)));
      }
    }

    $this->assertCount(2, array_intersect(['node:1', 'node:2'], $table['#cache']['tags']), 'Cache metadata of rendered referenced entities are added to the table.');
  }

  /**
   * Data provider for testing the table builder.
   */
  public function tableRenderingDataProvider() {
    return [
      'Standard table' => [
        [
          'show_entity_label' => TRUE,
          'view_mode' => 'teaser',
          'empty_cell_value' => '',
          'hide_header' => FALSE,
        ],
        [
          'title' => 'Title',
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        [
          ['Red Medium T', '$1.00', 'M', 'Red'],
          ['Green Large T', '$2.00', 'L', 'Green'],
        ],
      ],
      'No entity label' => [
        [
          'show_entity_label' => FALSE,
          'view_mode' => 'teaser',
          'empty_cell_value' => '',
          'hide_header' => FALSE,
        ],
        [
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        [
          ['$1.00', 'M', 'Red'],
          ['$2.00', 'L', 'Green'],
        ],
      ],
      'Incomplete rows' => [
        [
          'show_entity_label' => FALSE,
          'view_mode' => 'teaser',
          'empty_cell_value' => '',
          'hide_header' => FALSE,
        ],
        [
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        [
          ['$1.00', '', 'Red'],
          ['$2.00', 'L', 'Green'],
        ],
        TRUE,
      ],
      'Empty cell' => [
        [
          'show_entity_label' => FALSE,
          'view_mode' => 'teaser',
          'empty_cell_value' => 'N/A',
          'hide_header' => FALSE,
        ],
        [
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        [
          ['$1.00', 'N/A', 'Red'],
          ['$2.00', 'L', 'Green'],
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Tests interactions with view modes and displays of referenced entities.
   */
  public function testViewDisplays() {
    $this->displayRepository
      ->getViewDisplay('node', 't_shirt', 'default')
      ->setComponent('field_color')
      ->save();

    $base_settings = [
      'show_entity_label' => FALSE,
      'empty_cell_value' => '',
      'hide_header' => FALSE,
    ];

    $table = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($base_settings) {
      return $this->tableRenderer->getTable('node', 't_shirt', $this->nodes, [
        'view_mode' => 'does_not_exist',
      ] + $base_settings);
    });

    $this->assertArrayHasKey('field_color', $table['#header'], 'Use default view mode as fallback.');

    $this->displayRepository->getViewDisplay('node', 't_shirt', 'default')->delete();

    // Check that no error is raised even if there is no default view mode.
    $this->renderer->executeInRenderContext(new RenderContext(), function () use ($base_settings) {
      return $this->tableRenderer->getTable('node', 't_shirt', $this->nodes, [
        'view_mode' => 'does_not_exist',
      ] + $base_settings);
    });
  }

  /**
   * Tests handling of empty fields.
   */
  public function testEmptyFields() {
    $this->createField(['type' => 'string', 'field_name' => 'field_test'], ['bundle' => 't_shirt']);
    // Create a field which will be empty across all referenced entities to test
    // it gets filtered out.
    $this->createField(['type' => 'string', 'field_name' => 'field_empty'], ['bundle' => 't_shirt']);

    $this->displayRepository
      ->getViewDisplay('node', 't_shirt', 'teaser')
      ->setComponent('field_test')
      ->setComponent('field_empty')
      ->save();

    $node_0 = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 't_shirt',
      // Tests entity with less field values still has field present in table.
      'field_color' => 'Red',
      // Tests overlapping field value with other entity.
      'field_test' => $this->randomMachineName(),
    ]);
    $node_0->save();

    $node_1 = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 't_shirt',
      'field_size' => 'L',
      'field_price' => 15.00,
      'field_test' => $this->randomMachineName(),
    ]);
    $node_1->save();

    $nodes = [$node_0, $node_1];

    $table = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($nodes) {
      return $this->tableRenderer->getTable('node', 't_shirt', $nodes, [
        'show_entity_label' => FALSE,
        'view_mode' => 'teaser',
        'empty_cell_value' => '',
        'hide_header' => FALSE,
      ]);
    });

    $expected = [
      'field_price' => 'Price',
      'field_size' => 'Size',
      'field_color' => 'Color',
      'field_test' => 'field_test',
    ];
    $this->assertEquals($expected, $table['#header'], 'All non-empty fields across displayed entities are present.');
  }

  /**
   * Creates a field on the node entity type.
   *
   * @param array $storage_settings
   *   Settings to create the field storage with.
   * @param array $field_settings
   *   Settings to create the field instance with.
   */
  public function createField(array $storage_settings, array $field_settings) {
    $field_storage = FieldStorageConfig::create($storage_settings + ['entity_type' => 'node']);
    $field_storage->save();

    FieldConfig::create($field_settings + ['field_storage' => $field_storage])->save();
  }

}
