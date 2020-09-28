<?php

/**
 * @file
 * Contains ic_instagram.page.inc.
 *
 * Page callback for IC Instagram entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for IC Instagram templates.
 *
 * Default template: ic_instagram.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ic_instagram(array &$variables) {
  // Fetch IcInstagram Entity Object.
  $ic_instagram = $variables['elements']['#ic_instagram'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
