<?php

/**
 * @file
 * Node access hooks.
 *
 * Drupal's current node access system relies on two sub-systems:
 * - When an individual view is displayed, hook_node_access() is invoked.
 * - When a list of nodes is built, through an SQL query (typically with
 * Views),
 * hook_node_access() is not called, for performance reasons, and the query
 * is just altered to check a node access registry table.
 *
 * The problem is that core only registers a view grant in database, as the
 * update access is never used to retrieve lists.
 * As we need it, we build the grants that match core behavior: update access
 * is given per role and content type, for own or any nodes.
 *
 * If other modules alter the update access through hook_node_access(), they
 * should also implement hook_node_grants() and hook_node_access_records() to
 * be taken into account by this module. But no security worry if they don't,
 * the node might be listed if view access is allowed, but the edit access will
 * still be denied it the other module says so.
 *
 * We do not need to trigger an access registry update on our own as it's
 * already called by core when the node is saved, when permissions are saved or
 * when a module is enabled or disabled.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Session\AccountInterface;

/**
 * Implements hook_node_grants().
 *
 * @see node_query_node_access_alter()
 * @see node_node_access()
 */
function views_node_access_filter_node_grants(AccountInterface $account, $op) {

  $grants = [];
  if ($op == 'update') {
    foreach (array_keys(NodeType::loadMultiple()) as $type) {
      if ($account->hasPermission('edit any ' . $type . ' content')) {
        $grants['edit any ' . $type . ' content'] = [0];
      }
      elseif ($account->id() && $account->hasPermission('edit own ' . $type . ' content')) {
        $grants['edit own ' . $type . ' content'] = [$account->id()];
      }
    }
  }
  elseif ($op == 'view') {
    if ($account->hasPermission('view own unpublished content')) {
      $grants['view own unpublished content'] = [$account->id()];
    }
  }
  return $grants;
}

/**
 * Implements hook_node_access_records().
 *
 * @see views_node_access_filter_node_grants()
 */
function views_node_access_filter_node_access_records(NodeInterface $node) {

  $type = $node->bundle();
  $grants = [];

  $grants[] = [
    'realm' => 'edit any ' . $type . ' content',
    'gid' => 0,
    'grant_view' => 0,
    'grant_update' => 1,
    'grant_delete' => 0,
  ];

  if ($owner_id = $node->getOwnerId()) {
    $grants[] = [
      'realm' => 'edit own ' . $type . ' content',
      'gid' => $owner_id,
      'grant_view' => 0,
      'grant_update' => 1,
      'grant_delete' => 0,
    ];
  }

  return $grants;
}

/**
 * Implements hook_node_access_records_alter().
 */
function views_node_access_filter_node_access_records_alter(&$grants, NodeInterface $node) {
  // As soon as there is at least one record for a node, Drupal core does not
  // handle default view access on its side.
  // @see NodeAccessControlHandler::acquireGrants()
  // But our module doesn't want to alter default view access, so, if no other
  // module defines records, we mimic the default core behavior.
  if (!_views_node_access_filter_external_grants_are_defined($grants, $node)) {
    if ($node->isPublished()) {
      $grants[] = [
        'realm' => 'all',
        'gid' => 0,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
    }
    elseif ($owner_id = $node->getOwnerId()) {
      $grants[] = [
        'realm' => 'view own unpublished content',
        'gid' => $owner_id,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
    }
  }
}

/**
 * Tells if external grants are defined.
 */
function _views_node_access_filter_external_grants_are_defined($grants, $node) {
  $own_grants = views_node_access_filter_node_access_records($node);
  return count($grants) > count($own_grants);
}

/**
 * Implements hook_ENTITY_TYPE_update().
 */
function views_node_access_filter_user_role_update(EntityInterface $entity) {
  // Rebuild node grants when permissions change.
  node_access_needs_rebuild(TRUE);
}
