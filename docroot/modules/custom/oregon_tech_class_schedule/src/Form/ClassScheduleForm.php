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

namespace Drupal\oregon_tech_class_schedule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oregon_tech_class_schedule\ClassScheduleApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Use for Ajax.
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Class ClassScheduleForm
 *
 * @package Drupal\oregon_tech_class_schedule\Form
 */
class ClassScheduleForm extends FormBase {

  /**
   * Class schedule service object.
   *
   * @var \Drupal\oregon_tech_class_schedule\ClassScheduleApiService
   */
  protected $classScheduleApiService;

  /**
   * ClassScheduleForm constructor.
   *
   * @param \Drupal\oregon_tech_class_schedule\ClassScheduleApiService $classScheduleApiService
   */
  public function __construct(ClassScheduleApiService $classScheduleApiService) {
    $this->classScheduleApiService = $classScheduleApiService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('class_schedule.api_service')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'class_schedule_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $apiData = []) {

    $form['block_form'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['block_form col-sm-4'],
      ],
    ];

    $form['block_form']['subject'] = [
      '#type' => 'select',
      '#title' => $this->t('Subject'),
      '#options' => $apiData['subject'],
    ];

    $form['block_form']['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Level'),
      '#options' => $this->classScheduleApiService->getLevels(),
    ];

    $form['block_form']['term'] = [
      '#type' => 'select',
      '#title' => $this->t('Term'),
      '#options' => $apiData['term']['options'],
      '#default_value' => $apiData['term']['selected'],
    ];

    $form['block_form']['campus'] = [
      '#type' => 'select',
      '#title' => $this->t('Campus'),
      '#options' => $this->classScheduleApiService->getCampus(),
      '#default_value' => 'Klamath',
    ];

    $form['block_form']['materialCost'] = [
      '#type' => 'select',
      '#title' => $this->t('Cost of Materials'),
      '#options' => $this->classScheduleApiService->getCostOfMaterials(),
    ];

    $form['block_form']['days'] = [
      '#type' => 'checkboxes',
      '#options' => $this->classScheduleApiService->getDay(),
      '#title' => $this->t('Days Taught'),
      '#attributes' => [
        'class' => ['week-days'],
      ],
    ];

    $form['block_form']['days_message'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => [
        'class' => ['days-message']
      ],
      '#value' => $this->t('NOTE: Courses will be listed whose meeting days contain any of the selected days.'),
    ];

    $form['block_form']['time'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => ['start-time']
      ],
      '#title' => $this->t('Start Time Between'),
    ];

    $form['block_form']['time']['startTime'] = [
      '#type' => 'select',
      '#title' => $this->t('Start'),
      '#options' => $this->classScheduleApiService->getTime(),
    ];
    $form['block_form']['time']['endTime'] = [
      '#type' => 'select',
      '#title' => $this->t('End'),
      '#options' => $this->classScheduleApiService->getTime(),
    ];

    $form['block_form_result'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['block_form_result col-sm-8'],
        'id' => 'block_form_result',
      ],
    ];

    $form['block_form_result']['initial_message'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('No result.'),
    ];

    $form['block_form']['actions']['#type'] = 'actions';
    $form['block_form']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['search-class-schedule'],
      ],
      //      '#ajax' => [
      //        'callback' => '::getClassSchedule',
      //      ],
    ];

    $form['#attached']['library'][] = 'oregon_tech_class_schedule/class-schedule';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here, We are using ajax.
    // Form submit code is kept in js for speed optimization.
  }

  public function getClassSchedule(array $form, FormStateInterface $form_state) {

    // Nothing to do here, We are using ajax.
    // Form submit code is kept in js for speed optimization.

    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#block_form_result',
        '<div class="message">Submitted title is</div>')
    );
    return $response;

  }

}
