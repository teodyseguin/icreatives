<?php

/**
 * @file
 * Contains ic_fb_page_entity.page.inc.
 *
 * Page callback for Ic fb page entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Ic fb page entity templates.
 *
 * Default template: ic_fb_page_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ic_fb_page_entity(array &$variables) {
  // Fetch IcFbPageEntity Entity Object.
  $ic_fb_page_entity = $variables['elements']['#ic_fb_page_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
