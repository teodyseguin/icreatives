<?php

/**
 * @file
 * Functions to support theming layouts in the Particle theme.
 */

/**
 * Implements hook_preprocess_html().
 */
function particle_preprocess_html(&$variables) {
  $tools = \Drupal::service('ic_core.ic_core_tools');
  $path = $tools->getPath();

  if (!empty($path)) {
    $path = explode('/', $path);

    // We only want pages with single paths like /dashboard, /splash etc.
    if (count($path) == 2) {
      $path = $path['1'];
      $variables['html_attributes']->addClass($path);
    }
  }

  if ($tools->isFrontPage()) {
    $variables['html_attributes']->addClass('front');
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function particle_preprocess_page(&$variables) {
  // Themes are not able to declare module dependencies at this time.
  // We use this as a shortcut to guarantee the component library module exists.
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('components')) {
    $variables['has_components_module'] = TRUE;
  }
}
