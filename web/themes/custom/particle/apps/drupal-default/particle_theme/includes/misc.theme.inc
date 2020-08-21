<?php

/**
 * @file
 * Functions to support theming miscellaneous things the Particle theme.
 */

use Drupal\particle\Particle;

/**
 * Implements hook_preprocess().
 */
function particle_preprocess(&$variables) {
  // Set the paths.assets variable globally, so that drupal can
  // find webpack-compiled assets.
  $variables['paths']['assets'] = Particle::ASSETS_PATH;
  $variables['paths']['images'] = Particle::IMAGES_PATH;
  $variables['paths']['fonts'] = Particle::FONTS_PATH;
  $variables['paths']['svgs'] = Particle::SVGS_PATH;
}

/**
 * Implements hook_page_attachments_alter().
 */
function particle_page_attachments_alter(array &$page) {
  // Tell IE to use latest rendering engine (not to use compatibility mode).
  $ie_edge = [
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => [
      'http-equiv' => 'X-UA-Compatible',
      'content' => 'IE=edge',
    ],
  ];
  $page['#attached']['html_head'][] = [$ie_edge, 'ie_edge'];
}

/**
 * Implements hook_theme().
 */
function particle_theme() {
  $path = 'themes/custom/particle/apps/drupal-default/particle_theme/templates';

  return [
    'dashboard' => [
      'variables' => [],
      'path' => "$path/content",
    ],
    'splash_screen_content' => [
      'variables' => ['contents' => NULL],
      'path' => "$path/content",
    ],
  ];
}

/**
 * Implements hook_preprocess_commerce_product_variation().
 */
function particle_preprocess_commerce_product_variation(&$variables) {
  // Load SS Tools.
  $tools = \Drupal::service('ic_core.ic_core_tools');
  $product_variation_entity = $variables['product_variation_entity'];

  // Load the price.
  $price = $product_variation_entity->getPrice();
  $variables['price'] = $price;

  // Load product image.
  $field_image = $product_variation_entity->field_image->getValue();
  $file_storage = $tools->getStorage('file');
  $file = $file_storage->load($field_image[0]['target_id']);
  $variables['image'] = $file->get('uri')->value;

  // Load client.
  $client = $product_variation_entity->field_client->referencedEntities();

  if (!empty($client)) {
    $client = reset($client);
    $username = $client->get('name')->value;
    $first_name = $client->get('field_first_name')->value;
    $last_name = $client->get('field_last_name')->value;
    $display_name = $username;

    if (!empty($first_name)) {
      $display_name = $first_name;
    }

    if (!empty($last_name)) {
      $display_name .= ' ' . $last_name;
    }

    $variables['client'] = $display_name;
  }
}
