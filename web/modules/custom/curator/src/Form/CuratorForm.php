<?php
/**
 * Curator - Social post sync
 *
 * @package     curator
 * @author      Kwall <info@kwallcompany.com>
 * @license     GPL-2.0+
 * @link        http://www.kwallcompany.com/
 * @copyright   KwallCompany
 * Date:        06/26/2020
 * Time:        11:40 PM
 */

namespace Drupal\curator\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\curator\CuratorApiManager;
use Drupal\curator\CuratorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CuratorForm
 *
 * @package Drupal\curator\Form
 */
class CuratorForm extends EntityForm {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   *  CuratorManager object.
   *
   * @var \Drupal\curator\CuratorManager
   */
  protected $curatorManager;

  /**
   * Api manager object.
   *
   * @var \Drupal\curator\CuratorApiManager
   */
  protected $curatorApiManager;

  /**
   * CuratorForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param \Drupal\curator\CuratorManager $curatorManager
   * @param \Drupal\curator\CuratorApiManager $curatorApiManager
   */
  public function __construct(EntityTypeManager $entityTypeManager,
                              CuratorManager $curatorManager,
                              CuratorApiManager $curatorApiManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->curatorManager = $curatorManager;
    $this->curatorApiManager = $curatorApiManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('curator.manager'),
      $container->get('curator.api_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $curator = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $curator->label(),
      '#description' => $this->t("Label for the curator feed."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $curator->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$curator->isNew(),
    ];

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable this curator instance'),
      '#default_value' => $curator->isEnabled(),
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#description' => $this->t("Number of post to load from api."),
      '#default_value' => $curator->limit(),
    ];

    $feeds = $this->curatorApiManager->getFeeds();
    $feeds_options = [];
    if (!empty($feeds)) {
      foreach ($feeds as $feed) {
        $feeds_options[$feed->id] = $feed->name;
      }
    }
    $form['feed'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose Feed.'),
      '#options' => $feeds_options,
      '#disabled' => FALSE,
      '#required' => TRUE,
      '#default_value' => $curator->getFeedId() ? $curator->getFeedId() : '',
    ];

    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select content type'),
      '#required' => TRUE,
      '#options' => $this->curatorManager->getContentType(),
      '#empty_option' => t('-- Select Content Type  --'),
      '#default_value' => $curator->getContentType(),
      '#limit_validation_errors' => [['content_type']],
      '#submit' => ['::submitContentType'],
      '#executes_submit_callback' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxReplaceFieldsKeys',
        'wrapper' => 'field-key-settings',
        'method' => 'replace',
      ],
    ];

    $form['field_key_settings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="field-key-settings">',
      '#suffix' => '</div>',
    ];

    if (!empty($curator->getContentType())) {

      $form['field_key_settings']['field_mapping_form'] = [
        '#type' => 'container',
        '#prefix' => '<div id="field-mapping-form">',
        '#suffix' => '</div>',
      ];

      $x = $curator->getSettings();

      $form['field_key_settings']['field_mapping_form'] = $this->curatorManager->getMappingElements(
        $form['field_key_settings']['field_mapping_form'],
        $curator->getSettings(),
        $curator->getMappingList(),
        $curator->getContentType()
      );

    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $curator = $this->entity;

    $feed = $form_state->getValue('feed');
    $settings = [];

    if ($feed) {
      $mappings = $curator->getMappingList();
      foreach ($mappings as $key => $type) {
        $settings[$feed][$key] = $form_state->getValue($key);
      }
    }

    $curator->set('setting', $settings);
    $status = $curator->save();

    if ($status) {
      \Drupal::messenger()
        ->addMessage($this->t('Saved the %label curator.', [
          '%label' => $curator->label(),
        ]));
    }
    else {
      \Drupal::messenger()
        ->addMessage($this->t('The %label curator was not saved.', [
          '%label' => $curator->label(),
        ]));
    }

    $form_state->setRedirect('entity.curator.collection');
  }

  /**
   * Helper function to check whether an curator configuration entity
   * exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('curator')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }


  /**
   * Ajax replace field container.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function ajaxReplaceFields($form, FormStateInterface $form_state) {
    return $form['field_key_settings']['field_mapping_form'];
  }

  /**
   * Ajax replace field key container.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function ajaxReplaceFieldsKeys($form, FormStateInterface $form_state) {
    return $form['field_key_settings'];
  }

  /**
   * Handles submit call when content type is selected.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitContentType(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

}
