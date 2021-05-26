<?php
/**
 * OIT Class Schedule
 *
 * @package     oregon_tech_class_schedule
 * @author      Kwall <info@kwallcompany.com>
 * @license     GPL-2.0+
 * @link        http://www.kwallcompany.com/
 * @copyright   KwallCompany
 * Date:        07/28/2020
 * Time:        05:36 PM
 */

namespace Drupal\oregon_tech_class_schedule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oregon_tech_class_schedule\ClassScheduleApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\oregon_tech_class_schedule\Form\ClassScheduleForm;

/**
 * Provides a 'ClassScheduleBlock' Block.
 *
 * @Block(
 *   id = "class_schedule_block",
 *   admin_label = @Translation("Class Schedule Block"),
 *   category = @Translation("SITE Custom Block"),
 * )
 */
class ClassScheduleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form Builder Object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Class schedule api service.
   *
   * @var \Drupal\oregon_tech_class_schedule\ClassScheduleApiService
   */
  protected $classScheduleApiService;

  /**
   * ClassScheduleBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   * @param \Drupal\oregon_tech_class_schedule\ClassScheduleApiService $classScheduleApiService
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              FormBuilderInterface $formBuilder,
                              ClassScheduleApiService $classScheduleApiService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->classScheduleApiService = $classScheduleApiService;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('class_schedule.api_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Api data
    $apiData = [
      'term' => [],
      'subject' => [],
    ];

    // Get Terms.
    $apiData['term'] = $this->classScheduleApiService->getTerms();

    // Get Subjects
    $apiData['subject'] = $this->classScheduleApiService->getSubjects();

    $form = $this->formBuilder->getForm(
      ClassScheduleForm::class,
      $apiData
    );

    return $form;
  }

}
