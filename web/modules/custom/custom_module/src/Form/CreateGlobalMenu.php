<?php

namespace Drupal\custom_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Defines a form that configures forms module settings.
 */
class CreateGlobalMenu extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_menu';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Menu'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $academics = [
      (object) array('title' => 'Academics', 'nid' => null, 'alias' => '/academics'),
      (object) array('title' => 'Academics: Klamath Falls', 'nid' => null, 'alias' => '/academics/klamath-falls'),
      (object) array('title' => 'Academics: Online', 'nid' => null, 'alias' => '/academics/online'),
      (object) array('title' => 'Academics: Portland Metro', 'nid' => null, 'alias' => '/academics/portland'),
      (object) array('title' => 'Academics: Salem', 'nid' => null, 'alias' => '/academics/salem'),
    ];

    $nodes = \Drupal::database()->select('node_field_data', 'd')
      ->fields('d', ['nid', 'title'])
      ->condition('type', ['landing_page', 'layout_builder', 'program', 'online_program'], 'IN')
      ->orderBy('title')
      ->execute()
      ->fetchAll();

    $items = array_merge($academics, $nodes);

    $batch = array(
      'title' => t('Creating Menu links...'),
      'operations' => [],
      'init_message'     => t('Commencing'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message'    => t('An error occurred during processing'),
      'finished' => '\Drupal\batch_example\DeleteNode::ExampleFinishedCallback',
    );

    $pageChunks = array_chunk($items, 100);
    foreach ($pageChunks as $chunk) {
      $batch['operations'][] = ['\Drupal\custom_module\Form\CreateGlobalMenu::updateMenu',[$chunk]];
    }

    batch_set($batch);

  }

  /**
   * {@inheritdoc}
   */
  public static function updateMenu($items) {

    foreach($items as $item) {
      if (!empty($item->nid)) {
        $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $item->nid);
      }
      else {
        $alias = $item->alias;
      }

      $parent = NULL;
      if (!empty($alias)) {
        $aliasParts = explode('/', $alias);
        array_shift($aliasParts);
        if (count($aliasParts) > 9) {
          \Drupal::messenger()->addMessage($alias);
          continue;
        }
        array_pop($aliasParts);
        if (!empty($aliasParts)) {
          $path = '';
          foreach ($aliasParts as $parentTitle) {
            $path .= '/' . $parentTitle;
            $query = \Drupal::database()->select('menu_link_content', 'l');
            $query->join('menu_link_content_data', 'd', 'd.id=l.id');
            $query->fields('l', ['uuid']);
            $query->condition('d.menu_name', 'global-navigation');

            $nodePath = \Drupal::service('path.alias_manager')->getPathByAlias($path);
            if (preg_match('/node\/(\d+)/', $nodePath, $matches)) {
              $orCondition = $query->orConditionGroup()
                ->condition('d.link__uri', 'internal:' . $path)
                ->condition('d.link__uri', 'entity:node/' . $matches[1]);
              $query->condition($orCondition);
            }
            else {
              $query->condition('d.link__uri', 'internal:' . $path);
            }
            $uuid = $query->execute()->fetchField();
            if (empty($uuid)) {
              $parentTitle = ucwords(str_replace('-', ' ', $parentTitle));
              $menu_link = MenuLinkContent::create([
                'title' => $parentTitle,
                'link' => ['uri' => 'internal:' . $path],
                'menu_name' => 'global-navigation',
                'status' => TRUE,
                'parent' => $parent,
              ]);
              $menu_link->save();
              $uuid = $menu_link->uuid();
            }
            $parent = 'menu_link_content:' . $uuid;
          }
        }
      }

      $uri = !empty($item->nid) ? 'entity:node/' . $item->nid : 'internal:' . $alias;
      $query = \Drupal::database()->select('menu_link_content_data', 'd');
      $orCondition = $query->orConditionGroup()
        ->condition('link__uri', 'internal:' . $alias)
        ->condition('link__uri', $uri);
      $query->fields('d', ['id']);
      $query->condition($orCondition);
      $query->condition('menu_name', 'global-navigation');
      $id = $query->execute()->fetchField();

      if (empty($id)) {
        $menu_link = MenuLinkContent::create([
          'title' => $item->title,
          'link' => ['uri' => $uri],
          'menu_name' => 'global-navigation',
          'status' => TRUE,
          'parent' => $parent,
        ]);
      }
      else {
        $menu_link = MenuLinkContent::load($id);
        $menu_link->set('title', $item->title);
      }
      $menu_link->save();
    }
  }

}
