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
  $file_storage = $tools->getStorage('file');
  $image_style_storage = $tools->getStorage('image_style');

  // Default to turning off byline/submitted.
  $variables['display_submitted'] = FALSE;

  $node = $variables['node'];

  if ($node->bundle() == 'message') {
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

    $file_image = $node->field_image->getValue();

    if (!empty($file_image)) {
      $file_image = reset($file_image);
      $file_image = $file_storage->load($file_image['target_id']);

      $style = $image_style_storage->load('message_image');
      $variables['file_image'] = $style->buildUrl($file_image->get('uri')->value);
    }
  }
}
