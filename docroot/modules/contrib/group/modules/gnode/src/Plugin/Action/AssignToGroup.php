<?php

namespace Drupal\gnode\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentType;

/**
 * Relate nodes with group.
 *
 * @Action(
 *   id = "gnode_assign_node_to_group",
 *   label = @Translation("Add selected nodes to group"),
 *   type = "node",
 * )
 */
class AssignToGroup extends ViewsBulkOperationsActionBase {

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
      // Check whether this node type is able to be added to this group.
      $plugin_id = 'group_node:' . $node->bundle();
      $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
      if (empty($group_content_types)) {
        $group_type_id = $group->getGroupType()->id();
        $messenger = \Drupal::messenger();
        $messenger->addMessage(
          $this->t('The <a href="@url">Group content plugin</a> is not installed for node type %bundle, so those nodes could not be assigned to group %group.',
            [
              '@url' => '/admin/group/types/manage/' . $group_type_id . '/content',
              '%bundle' => $node->bundle(),
              '%group' => $group->label(),
            ]),
          'warning');
        return;
      }
      // Check whether this node already belongs to the group.
      $group_contents = \Drupal::entityTypeManager()
        ->getStorage('group_content')
        ->loadByProperties([
          'type' => array_keys($group_content_types),
          'entity_id' => $node->id(),
          'gid' => $group_id,
        ]);
      // If not already assigned, add this node to the group.
      if (empty($group_contents)) {
        $group->addContent($node, $plugin_id);
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
