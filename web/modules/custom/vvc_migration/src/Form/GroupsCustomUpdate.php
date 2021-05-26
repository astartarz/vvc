<?php
/**
 * @file
 * Contains Drupal\ccsf_custom\Form\GroupForm.
 */
namespace Drupal\vvc_migration\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\group\Entity\Group;
use Drupal\group_content_menu\Entity\GroupContentMenuType;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\user\Entity\User;

class GroupsCustomUpdate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groups_custom_update';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['action'] = [
      '#type' => 'select',
      '#options' => [
        'links' => 'Remove Groups Menu Links',
        'menus' => 'Relate menu to group by name',
        'add' => 'Add Page menu links to Group menus',
        'create' => 'Relate pages to groups',
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('action');
    switch ($action) {
      case 'links':
        $mids = \Drupal::entityQuery('menu_link_content')
          ->condition('menu_name', "%group_menu_link_content-%", 'LIKE')
          ->execute();
        $controller = \Drupal::entityTypeManager()->getStorage('menu_link_content');
        $entities = $controller->loadMultiple($mids);
        $controller->delete($entities);
        $message = "All groups menu links removed";
        break;

      case 'menus':
        $groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple();
        $menuPluginId = 'group_content_menu:site_group_menu';
        foreach ($groups as $group) {
          $menuInstances = \Drupal::entityTypeManager()->getStorage('group_content')->loadByGroup($group, $menuPluginId);
          if (empty($menuInstances)) {
            $menuId = $group->id() - 1;
            $groupMenu = MenuLinkContent::load($menuId);
            if (!empty($groupMenu)) {
              $group_content = \Drupal::entityTypeManager()->getStorage('group_content')->create([
                'type' => $menuPluginId,
                'gid' => $group->id(),
                'label' => $group->label(),
                'entity_id' => $groupMenu,
              ]);
              $group_content->save();
            }
          }
        }
        break;

      case 'add':
        $groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple();
        $batchBuilder = new BatchBuilder();
        $batchBuilder
          ->setTitle($this->t('Processing'))
          ->setInitMessage($this->t('Initializing.'))
          ->setProgressMessage($this->t('Completed @current of @total.'))
          ->setErrorMessage($this->t('An error has occurred.'));

        $batchBuilder->setFile(drupal_get_path('module', 'vvc_migration') . '/src/Form/GroupsCustomUpdate.php');
        $batchBuilder->addOperation([$this, 'processItems'], [$groups]);
        $batchBuilder->setFinishCallback([$this, 'finished']);

        batch_set($batchBuilder->toArray());

        break;

      case 'create':
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'page');
        $pages = $query->execute();
        $batchBuilder = new BatchBuilder();
        $batchBuilder
          ->setTitle($this->t('Processing'))
          ->setInitMessage($this->t('Initializing.'))
          ->setProgressMessage($this->t('Completed @current of @total.'))
          ->setErrorMessage($this->t('An error has occurred.'));

        $batchBuilder->setFile(drupal_get_path('module', 'vvc_migration') . '/src/Form/GroupsCustomUpdate.php');
        $batchBuilder->addOperation([$this, 'createItems'], [$pages]);
        $batchBuilder->setFinishCallback([$this, 'finished']);

        batch_set($batchBuilder->toArray());

        break;
    }
    if (!empty($message)) {
      \Drupal::messenger()->addMessage($message);
    }
  }

  /**
   * Processor for batch operations.
   */
  public function processItems($items, array &$context) {
    // Elements per operation.
    $limit = 1;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      foreach ($context['sandbox']['items'] as $item) {
        if ($counter != $limit) {
          $groupContent = \Drupal::entityTypeManager()->getStorage('group_content')->loadByProperties(['gid' => $item->id(), 'type' => 'site_group-group_node-page']);
          if (!empty($groupContent)) {
            $menuInstances = \Drupal::entityTypeManager()->getStorage('group_content')->loadByGroup($item, 'group_content_menu:site_group_menu');
            if (!empty($menuInstances)) {
              $menuInstance = reset($menuInstances);
              if ($menuInstance->hasField('entity_id')) {
                $menu_name = GroupContentMenuInterface::MENU_PREFIX . $menuInstance->get('entity_id')->target_id;
                foreach ($groupContent as $item) {
                  $node = $item->getEntity();
                  if ($node->id()) {
                    $query = \Drupal::entityQuery('menu_link_content')
                      ->condition('link.uri', 'entity:node/' . $node->id())
                      ->condition('menu_name', $menu_name)
                      ->sort('id', 'ASC')
                      ->range(0, 1);
                    $result = $query->execute();

                    if (empty($result)) {
                      $menu_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->create([
                        'title' => $node->label(),
                        'link' => [
                          'uri' => 'entity:node/' . $node->id(),
                        ],
                        'menu_name' => $menu_name,
                      ]);
                      $menu_link->save();
                    }

                  }
                }
              }
            }
          }

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing group :progress of :count', [
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);

          // Increment total processed item values. Will be used in finished
          // callback.
          $context['results']['processed'] = $context['sandbox']['progress'];
        }
        else {
          break;
        }
      }
    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Processor for batch operations.
   */
  public function createItems($items, array &$context) {
    // Elements per operation.
    $limit = 10;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      foreach ($context['sandbox']['items'] as $item) {
        if ($counter != $limit) {
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($item);
          $groupNames = [];
          $departments = $node->get('field_related_department')->getValue();
          if (!empty($departments)) {
            foreach ($departments as $dept) {
              $deptNode = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($dept['target_id']);
              $groupNames[] = $deptNode->label();
            }
          }
          $groupName = $node->get('field_legacy_group')->value;
          if (!empty($groupName)) {
            $groupNames[] = $groupName;
          }

          foreach ($groupNames as $groupName) {
            $group = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['label' => $groupName]);
            if (!empty($group)) {
              $group = reset($group);
            }
            else {
              $group = Group::create([
                'type' => 'site_group',
                'label' => $groupName
              ]);
              $group->setOwner(User::load(1));
              $group->enforceIsNew();
              $group->save();
            }
            $groupContent = \Drupal::entityTypeManager()->getStorage('group_content')->loadByProperties(['gid' => $group->id(), 'type' => 'site_group-group_node-page', 'label' => $node->label()]);
            if (empty($groupContent)) {
              $group->addContent($node, 'group_node:' . $node->getType());
            }
          }

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing group :progress of :count', [
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);

          // Increment total processed item values. Will be used in finished
          // callback.
          $context['results']['processed'] = $context['sandbox']['progress'];
        }
        else {
          break;
        }
      }
    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished callback for batch.
   */
  public function finished($success, $results, $operations) {
    $message = $this->t('Number of groups affected by batch: @count', [
      '@count' => $results['processed'],
    ]);

    $this->messenger()
      ->addStatus($message);
  }
}
