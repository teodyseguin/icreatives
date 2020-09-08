<?php

/**
 * @file
 * Functions to support theming content in the Particle theme.
 */

/**
 * @file
 * Functions to support theming node entities in the Particle theme.
 */

/**
 * Implements hook_preprocess_comment().
 */
function particle_preprocess_comment(array &$variables) {
  $tools = \Drupal::service('ic_core.ic_core_tools');

  $author_id = $variables['author_id'];
  $variables['client_name'] = $tools->getUserFullName($author_id);
}
