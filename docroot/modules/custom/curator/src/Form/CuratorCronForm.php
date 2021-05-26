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

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\curator\Batch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CuratorCronForm
 *
 * @package Drupal\curator\Form
 */
class CuratorCronForm extends FormBase {

  /**
   * Curator settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $curatorSettings;

  /**
   * CuratorCronForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->curatorSettings = $configFactory->getEditable('curator.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'curator_cron';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $cron_key = $this->curatorSettings->get('cron_key');
    $cron_key = ($cron_key) ? $cron_key : md5(microtime() . rand());

    $form['cron_setting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cron Key Settings'),
      '#description' => sprintf(
        $this->t("Your cron endpoint is") . ' <b>' . $base_url . '/curator/cron?key=%s</b>',
        $cron_key),
    ];
    $form['cron_setting']['cron_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cron Key'),
      '#default_value' => $cron_key,
      '#description' => $this->t('You can change this key as per your need.'),
    ];

    $form['run_cron_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run batch import now'),
      '#description' => $this->t('This will run batch import just after hitting the "Save settings" button.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Settings'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save form settings.
    $cron_key = $form_state->getValue('cron_key');
    $cron_key = ($cron_key) ? $cron_key : md5(microtime() . rand());
    $this->curatorSettings->set('cron_key', $cron_key)->save();

    // Run post import batch to import
    // Post from curator.
    if ($form_state->getValue('run_cron_now')) {
      $batch = new Batch();
      $batch->prepareOperations()->start();
    }

    // Send success message.
    \Drupal::messenger()->addMessage($this->t('Cron settings save.'));

  }

}
