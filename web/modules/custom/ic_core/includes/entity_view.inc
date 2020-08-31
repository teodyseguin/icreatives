<?php

/**
 * @file
 * entity_view.inc
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Impements hook_node_access().
 */
function ic_core_entity_view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
  $tools = \Drupal::service('ic_core.ic_core_tools');
  $user_storage = $tools->getStorage('user');

  if ($view_mode == 'full' && $entity->bundle() == 'message') {
    $assigned_client = $entity->field_client->referencedEntities();

    if (empty($assigned_client)) {
      return;
    }

    $assigned_client = reset($assigned_client);
    $assigned_client_id = $assigned_client->id();
    $current_user_id = $tools->getCurrentUser()->id();

    // If the assigned client's id is equal to the current logged in user's id,
    // meaning the assigned client is viewing the message content that was meant for him/her.
    if ($assigned_client_id == $current_user_id) {
      // Then we want to deduct 1 from the current message count for this user.
      $assigned_client = $user_storage->load($assigned_client->id());

      $assigned_client->set('field_current_message_count', 0);
      $assigned_client->save();
    }
  }
}
