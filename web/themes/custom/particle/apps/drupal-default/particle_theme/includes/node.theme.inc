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
  $commerce_product_variation_storage = $tools->getStorage('commerce_product_variation');
  $term_storage = $tools->getStorage('taxonomy_term');

  // Default to turning off byline/submitted.
  $variables['display_submitted'] = FALSE;

  $node = $variables['node'];

  if ($node->bundle() == 'message') {
    $product = $node->field_product_name->referencedEntities();
    $client = $node->field_client->referencedEntities();
    $tags = $node->field_message_tags->getValue();

    if (!empty($product)) {
      $product = reset($product);
      $variables['product_name'] = $product->getTitle();
      $variations = $product->variations->getValue();

      if (!empty($variations)) {
        $variation_id = $variations[0]['target_id'];
        $product_variation = $commerce_product_variation_storage->load($variation_id);
        $product_variation_image = $product_variation->field_image->getValue();

        if (!empty($product_variation_image)) {
          $product_variation_image = reset($product_variation_image);
          $product_variation_image = $file_storage->load($product_variation_image['target_id']);
          $style = $image_style_storage->load('message_image');
          $variables['file_image'] = $style->buildUrl($product_variation_image->get('uri')->value);
        }
      }
    }

    if (!empty($tags)) {
      foreach ($tags as $tag) {
        $term_name = $term_storage->load($tag['target_id'])->get('name')->value;
        $term_link = "/inbox?field_message_tags_target_id=" . urlencode($term_name);
        $variables['tags'][] = [
          'link' => $term_link,
          'name' => $term_name,
        ];
      }
    }

    if (!empty($client)) {
      $client = reset($client);
      $variables['client_name'] = $tools->getUserFullName($client->id());
    }
  }
}
