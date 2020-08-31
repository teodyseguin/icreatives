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

    $current_message_count = $client->get('field_current_message_count')->value;
    $previous_message_count = $client->get('field_previous_message_count')->value;

    $client->set('field_current_message_count', $current_message_count + 1);
    $client->set('field_previous_message_count', $previous_message_count + 1);
    $client->save();
  }

  if ($entity->bundle() == 'messages') {
    $entity_id = $entity->entity_id->getValue();

    if (empty($entity_id)) {
      return;
    }

    $entity_id = reset($entity_id);
    $message_node = $node_storage->load($entity_id['target_id']);
    $client = $message_node->field_client->referencedEntities();

    if (empty($client)) {
      return;
    }

    $client = reset($client);
    $client = $user_storage->load($client->id());

    $current_message_count = $client->get('field_current_message_count')->value;
    $previous_message_count = $client->get('field_previous_message_count')->value;

    $client->set('field_current_message_count', $current_message_count + 1);
    $client->set('field_previous_message_count', $previous_message_count + 1);
    $client->save();
  }
}
