<?php

namespace Drupal\oregon_tech_layout_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Defines a form that configures forms module settings.
 */
class LegacyAlias extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_urls';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update aliases'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $paths = \Drupal::database()->select('node__field_legacy_path', 'p')
      ->fields('p', ['entity_id', 'field_legacy_path_value'])
      ->execute()
      ->fetchAll();

    $batch = array(
      'title' => t('Updating aliases...'),
      'operations' => [],
      'init_message'     => t('Commencing'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message'    => t('An error occurred during processing'),
      'finished' => '\Drupal\batch_example\DeleteNode::ExampleFinishedCallback',
    );

    $chunks = array_chunk($paths, 50);
    foreach ($chunks as $chunk) {
      $batch['operations'][] = ['\Drupal\oregon_tech_layout_migration\Form\LegacyAlias::updateUrls',[$chunk]];
    }

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public static function updateUrls($items) {
    foreach ($items as $item) {
      $nid = $item->entity_id;
      $path = $item->field_legacy_path_value;

      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/'. $nid);
      if ($alias != $path) {
        $node = Node::load($nid);
        $node->path->pathauto = 0;
        $node->save();
        $pid = $node->path->pid;
        $pathAlias = PathAlias::load($pid);
        $pathAlias->setAlias($path);
        $pathAlias->save();
      }

    }
  }

}
