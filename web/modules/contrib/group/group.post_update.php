<?php

/**
 * @file
 * Post update functions for Group.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupContentType;
use Drupal\user\Entity\Role;

/**
 * Recalculate group type and group content type dependencies after moving the
 * plugin configuration from the former to the latter in group_update_8006().
 */
function group_post_update_group_type_group_content_type_dependencies() {
  foreach (GroupType::loadMultiple() as $group_type) {
    $group_type->save();
  }

  foreach (GroupContentType::loadMultiple() as $group_type) {
    $group_type->save();
  }
}

/**
 * Recalculate group content type dependencies after updating the group content
 * enabler base plugin dependency logic.
 */
function group_post_update_group_content_type_dependencies() {
  foreach (GroupContentType::loadMultiple() as $group_type) {
    $group_type->save();
  }
}

/**
 * Grant the new 'access group overview' permission.
 */
function group_post_update_grant_access_overview_permission() {
  /** @var \Drupal\user\RoleInterface $role */
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('administer group')) {
      $role->grantPermission('access group overview');
      $role->save();
    }
  }
}

/**
 * Fix cache contexts in views.
 */
function group_post_update_view_cache_contexts(&$sandbox) {
  if (!\Drupal::moduleHandler()->moduleExists('views')) {
    return;
  }
  // This will trigger the catch-all fix in group_view_presave().
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) {
    return TRUE;
  });
}

/**
 * Restore the data for group_content entity_id.
 */
function group_post_update_restore_entity_id_data(&$sandbox) {
  $query = \Drupal::database()
    ->select('group_content_entity_id_update', 'g')
    ->fields('g', ['id', 'entity_id']);

  // Initialize the update process, install the field schema.
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = $query->countQuery()->execute()->fetchField();
    $sandbox['current'] = 0;
  }

  // We're now inserting new fields data which may be tricky. We're updating
  // group_content entities instead of inserting fields data directly to make
  // sure field data is stored correctly.
  $rows_per_operation = 50;
  $query->condition('id', $sandbox['current'], '>');
  $query->range(0, $rows_per_operation);
  $query->orderBy('id', 'ASC');

  $rows = $query->execute()->fetchAllKeyed();
  if ($rows) {
    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadMultiple(array_keys($rows));

    foreach ($group_contents as $id => $group_content) {
      $group_content->entity_id->target_id = $rows[$id];
      $group_content->save();
    }

    end($rows);
    $sandbox['current'] = key($rows);
    $moved_rows = Drupal::database()
      ->select('group_content__entity_id')
      ->countQuery()->execute()->fetchField();
    $sandbox['#finished'] = ($moved_rows / $sandbox['total']);
  }
  else {
    $sandbox['#finished'] = 1;
  }

  if ($sandbox['#finished'] >= 1) {
    // Delete the temporary table once data is copied.
    \Drupal::database()->schema()->dropTable('group_content_entity_id_update');
  }
}

/**
 * Fix existing group content views relationships.
 */
function group_post_update_fix_group_content_views_relations() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('views.view.') as $name) {
    $view = $config_factory->getEditable($name);
    $changed = FALSE;
    foreach ($view->get('display') as $display_id => $display) {
      if (isset($display['display_options']['relationships'])) {
        foreach ($display['display_options']['relationships'] as $relation_id => $relation) {
          if ($relation['table'] == 'group_content_field_data') {
            $trail = "display.$display_id.display_options.relationships.$relation_id.table";
            $view->set($trail, 'group_content__entity_id')->save();
            $changed = TRUE;
          }
        }
      }
    }
    if ($changed) {
      $view->save();
    }
  }
}
