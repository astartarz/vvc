<?php

namespace Drupal\gnode\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentType;

/**
 * Remove relation between nodes and group.
 *
 * @Action(
 *   id = "gnode_remove_node_from_group",
 *   label = @Translation("Remove selected nodes from group"),
 *   type = "node",
 * )
 */
class RemoveFromGroup extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(NodeInterface $node = NULL) {
    $params = $this->context['redirect_url']->getRouteParameters();
    if ($node === NULL || !in_array('group', array_keys($params))) {
      return;
    }
    $group_id = $this->context['redirect_url']->getRouteParameters()['group'];
    /** @var Group $group */
    $group = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->load($group_id);
    if (!empty($group) && $group instanceof Group) {
      $plugin_id = 'group_node:' . $node->bundle();
      $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
      if (empty($group_content_types)) {
        return;
      }
      $group_contents = \Drupal::entityTypeManager()
        ->getStorage('group_content')
        ->loadByProperties([
          'type' => array_keys($group_content_types),
          'entity_id' => $node->id(),
          'gid' => $group_id,
        ]);
      foreach ($group_contents as $group_content) {
        $group_content->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $current_user = \Drupal::currentUser();
    $group_id = $this->context['redirect_url']->getRouteParameters()['group'];
    /** @var Group $group */
    $group = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->load($group_id);
    if (!empty($group) && $group instanceof Group) {
      return $group->hasPermission('access group_node overview', $current_user);
    }

    return FALSE;
  }

}
