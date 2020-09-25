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

  // We want to get the message counts per current logged in user,
  // and store it to the drupalSettings.
  $tools = \Drupal::service('ic_core.ic_core_tools');
  $user_data = $tools->getUserData();

  $user_data = $tools->getUserData();
  $current_user_id = $tools->getCurrentUser()->id();

  $variables['#attached']['drupalSettings']['ic']['messages'] = $user_data->get('ic_core', $current_user_id, 'messages');
  $variables['#attached']['drupalSettings']['ic']['fb_access_token'] = $user_data->get('ic_core', $current_user_id, 'fb_access_token');
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
  $roles = $tools->getCurrentRoles();
  $image_style_storage = $tools->getStorage('image_style');
  $commerce_storage = $tools->getStorage('commerce_currency');
  $product_variation_entity = $variables['product_variation_entity'];

  if (in_array('site_admin', $roles) || in_array('administrator', $roles)) {
    $variables['allow_edit'] = TRUE;
  }

  // Load the price.
  $price = $product_variation_entity->getPrice();
  $currency_code = $price->getCurrencyCode();
  $price = str_replace($currency_code, '', $price);
  $currency_symbol = $commerce_storage->load($currency_code)->getSymbol();
  $variables['price'] = "$currency_symbol$price";

  // Load product image.
  $field_image = $product_variation_entity->field_image->getValue();
  $file_storage = $tools->getStorage('file');
  $file = $file_storage->load($field_image[0]['target_id']);

  $style = $image_style_storage->load('product_image_92_94');

  if (!empty($file)) {
    $variables['image'] = $style->buildUrl($file->get('uri')->value);
  }

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
