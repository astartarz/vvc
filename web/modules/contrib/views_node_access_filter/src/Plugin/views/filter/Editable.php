<?php

namespace Drupal\views_node_access_filter\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Views filter plugin.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_node_access_filter_editable")
 */
class Editable extends FilterPluginBase {

  /**
   * {@inheritdoc}
   *
   * @see views_node_access_filter_query_views_node_access_filter_editable_alter()
   */
  public function query() {
    if ($this->query instanceof Sql) {
      $this->ensureMyTable();
      $this->query->addTag('views_node_access_filter_editable');
    }
    else {
      throw new \Exception("Editable filter is only compatible with SQL views.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user';

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

}
