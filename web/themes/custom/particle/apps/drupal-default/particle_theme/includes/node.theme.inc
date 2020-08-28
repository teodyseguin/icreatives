<?php

/**
 * @file
 * Functions to support theming node entities in the Particle theme.
 */

/**
 * Implements hook_preprocess_node().
 */
function particle_preprocess_node(array &$variables) {
  $tools = \Drupal::service('ic_core.ic_core_tools');

  // Default to turning off byline/submitted.
  $variables['display_submitted'] = FALSE;

  $node = $variables['node'];
  $product = $node->field_product_name->referencedEntities();
  $client = $node->field_client->referencedEntities();

  if (!empty($product)) {
    $product = reset($product);
    $variables['product_name'] = $product->getTitle();
  }

  if (!empty($client)) {
    $client = reset($client);
    $variables['client_name'] = $tools->getUserFullName($client->id());
  }
}
