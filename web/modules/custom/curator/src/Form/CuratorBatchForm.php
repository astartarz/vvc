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

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\curator\Batch;

/**
 * Class CuratorBatchForm
 *
 * @package Drupal\curator\Form
 */
class CuratorBatchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'curator_batch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#markup' => "<p><i>" . $this->t('This will run batch importer for all channel set in entities tab.') . "</i></p>",
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Batch Import'),
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

    // Run post import batch to import
    // Post from curator.
    $batch = new Batch();
    $batch->prepareOperations()->start();

    \Drupal::messenger()->addMessage($this->t('Batch process complete.'));
  }

}
