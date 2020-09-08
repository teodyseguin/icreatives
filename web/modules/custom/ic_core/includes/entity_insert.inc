<?php

/**
 * @file
 * entity_insert.inc
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Impements hook_entity_insert().
 */
function ic_core_entity_insert(EntityInterface $entity) {
  $tools = \Drupal::service('ic_core.ic_core_tools');
  $user_data = \Drupal::service('user.data');
  $node_storage = $tools->getStorage('node');
  $user_storage = $tools->getStorage('user');

  if ($entity->bundle() == 'message') {
    $client = $entity->field_client->referencedEntities();

    if (empty($client)) {
      return;
    }

    $client = reset($client);
    $user_storage = $tools->getStorage('user');
    $client = $user_storage->load($client->id());

    $messages = $user_data->get('ic_core', $client->id(), 'messages');
    $messages[$entity->id()] = $messages[$entity->id()] + 1;
    $user_data->set('ic_core', $client->id(), 'messages', $messages);
  }

  // When a message node receives a comment (messages is comment type),
  // we just wanted to make sure that current is not zero.
  // If it's zero, then we add at least 1 to it.
  if ($entity->bundle() == 'messages') {
    $site_admins = $tools->getSiteAdmin();
    $current_roles = $tools->getCurrentRoles();

    $entity_id = $entity->entity_id->getValue();
    $entity_id = reset($entity_id);
    $message_node = $node_storage->load($entity_id['target_id']);
    $client = $message_node->field_client->referencedEntities();

    if (in_array('site_admin', $current_roles)) {
      if (!empty($client)) {
        $client = reset($client);
        $client = $user_storage->load($client->id());
        ic_core_update_counters($message_node, $client);
      }
    }
    else {
      foreach ($site_admins as $site_admin) {
        ic_core_update_counters($message_node, $site_admin);
      }
    }
  }
}

/**
 * Update the message counters.
 */
function ic_core_update_counters($entity, $client) {
  $user_data = \Drupal::service('user.data');
  $messages = $user_data->get('ic_core', $client->id(), 'messages');
  $current_message_count = $messages[$entity->id()];

  if ($current_message_count == 0) {
    $messages[$entity->id()] = $current_message_count + 1;
    $user_data->set('ic_core', $client->id(), 'messages', $messages);
  }
}
