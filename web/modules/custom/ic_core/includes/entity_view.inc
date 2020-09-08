<?php

/**
 * @file
 * entity_view.inc
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Impements hook_entity_view().
 */
function ic_core_entity_view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
  $tools = \Drupal::service('ic_core.ic_core_tools');
  $current_roles = $tools->getCurrentRoles();
  $site_admins = $tools->getSiteAdmin();
  $user_data = \Drupal::service('user.data');

  if ($view_mode == 'full' && $entity->bundle() == 'message') {
    $assigned_client = $entity->field_client->referencedEntities();

    if (empty($assigned_client)) {
      return;
    }

    $assigned_client = reset($assigned_client);
    $assigned_client_id = $assigned_client->id();
    $current_user_id = $tools->getCurrentUser()->id();

    if ($assigned_client_id == $current_user_id) {
      $messages = $user_data->get('ic_core', $current_user_id, 'messages');

      if (empty($messages)) {
        return;
      }

      $current_message_count = $messages[$entity->id()];

      if ($current_message_count != 0) {
        $messages[$entity->id()] = $current_message_count - 1;
        $user_data->set('ic_core', $current_user_id, 'messages', $messages);
      }
    }

    if (in_array('site_admin', $current_roles)) {
      foreach ($site_admins as $site_admin) {
        $messages = $user_data->get('ic_core', $site_admin->id(), 'messages');

        if (empty($messages)) {
          continue;
        }

        $current_message_count = $messages[$entity->id()];

        if ($current_message_count != 0) {
          $messages[$entity->id()] = $current_message_count - 1;
          $user_data->set('ic_core', $site_admin->id(), 'messages', $messages);
        }
      }
    }
  }
}
