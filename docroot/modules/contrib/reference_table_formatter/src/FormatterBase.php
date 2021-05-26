<?php

namespace Drupal\reference_table_formatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FormatterBase as CoreFormatterBase;

/**
 * A base field formatter class for rendering tables.
 */
abstract class FormatterBase extends CoreFormatterBase implements ContainerFactoryPluginInterface, FormatterInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * Entity to table renderer.
   *
   * @var \Drupal\reference_table_formatter\EntityToTableRenderer
   */
  protected $referenceRenderer;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if ($entities = $this->getEntitiesToView($items)) {
      $field_def = $items[0]->getFieldDefinition();
      // Return an array so that field labels still work.
      return [
        $this->referenceRenderer->getTable($this->getTargetEntityId($field_def), $this->getTargetBundleId($field_def), $entities, $this->getSettings()),
      ];
    }
    return [];
  }

  /**
   * Get the entities which will make up the table.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   *
   * @return array
   *   An array of loaded entities.
   */
  protected function getEntitiesToView(FieldItemListInterface $items) {
    $entity_type = $this->getTargetEntityId($this->fieldDefinition);
    $entity_storage = $this->entityManager->getStorage($entity_type);
    $entities = [];
    foreach ($items as $item) {
      $entity_id = $this->getEntityIdFromFieldItem($item);
      if ($entity_id) {
        $entity = $entity_storage->load($entity_id);
        if ($entity && $entity->access('view')) {
          $entities[] = $entity;
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $view_modes = $this->getConfigurableViewModes();
    if (!empty($view_modes)) {
      $form['view_mode'] = [
        '#title' => $this->t('View Mode'),
        '#description' => $this->t('Select the view mode which will control which fields are shown and the display settings of those fields.'),
        '#type' => 'select',
        '#default_value' => $this->getSettings()['view_mode'],
        '#options' => $this->getConfigurableViewModes(),
      ];
    }
    $form['show_entity_label'] = [
      '#title' => $this->t('Display Entity Label'),
      '#description' => $this->t('Should the label of the target entity be displayed in the table?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings()['show_entity_label'],
    ];
    $form['hide_header'] = [
      '#title' => $this->t('Hide Table Header'),
      '#description' => $this->t('Should the table header be displayed?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings()['hide_header'],
    ];
    $form['empty_cell_value'] = [
      '#title' => $this->t('Empty Cell Value'),
      '#description' => $this->t('The string which should be displayed in empty cells. Defaults to an empty string.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings()['empty_cell_value'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => 'default',
      'show_entity_label' => 0,
      'empty_cell_value' => '',
      'hide_header' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return ['#markup' => $this->t('Showing a table of rendered @view_mode entity fields.', ['@view_mode' => $this->getSetting('view_mode')])];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurableViewModes() {
    return $this->displayRepository->getViewModeOptions($this->getTargetEntityId($this->fieldDefinition));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('reference_table_formatter.renderer'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Constructs a new ReferenceTableFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param EntityToTableRenderer $reference_renderer
   *   The entity-to-table renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The Entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityToTableRenderer $reference_renderer, EntityTypeManagerInterface $entity_manager, EntityDisplayRepositoryInterface $display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->referenceRenderer = $reference_renderer;
    $this->entityManager = $entity_manager;
    $this->displayRepository = $display_repository;
  }

}
