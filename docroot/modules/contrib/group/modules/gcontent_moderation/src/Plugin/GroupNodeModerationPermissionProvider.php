<?php

namespace Drupal\gcontent_moderation\Plugin;

use Drupal\workflows\Entity\Workflow;
use Drupal\group\Plugin\GroupContentPermissionProvider;

/**
 * Provides content moderation specific permissions for group node entities.
 */
class GroupNodeModerationPermissionProvider extends GroupContentPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function getEntityViewUnpublishedPermission($scope = 'any') {
    if ($scope === 'any') {
      // Backwards compatible permission name for 'any' scope.
      return "view unpublished $this->pluginId entity";
    }
    return parent::getEntityViewUnpublishedPermission($scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $parent_permissions = parent::buildPermissions();
    $permissions = [];

    $t_args = [
      '%plugin_name' => $this->definition['label'],
      '%entity_type' => $this->definition['entity_bundle'],
    ];
    $defaults = ['title_args' => $t_args, 'description_args' => $t_args];

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    foreach (Workflow::loadMultipleByType('content_moderation') as $workflow) {
      $defaults['title_args']['%workflow'] = $workflow->label();

      $entity_types = $workflow->get('type_settings')['entity_types'];
      $bundles = isset($entity_types['node']) ? $entity_types['node'] : [];

      if (in_array($this->definition['entity_bundle'], $bundles)) {
        foreach ($workflow->getTypePlugin()->getTransitions() as $transition) {
          $defaults['title_args']['%transition'] = $transition->label();

          $permissions["use {$workflow->id()} transition {$transition->id()} for {$this->pluginId}"] = [
            "title" => "%plugin_name - Use %transition transition from %workflow workflow",
          ] + $defaults;
        }
      }
    }

    return $permissions + $parent_permissions;
  }

}