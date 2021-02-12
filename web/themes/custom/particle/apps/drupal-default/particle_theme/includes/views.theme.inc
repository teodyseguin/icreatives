<?php

/**
 * @file
 * Functions to support theming views in the Particle theme.
 */

/**
 * Implements template_preprocess_views_view().
 */
function particle_preprocess_views_view(&$variables) {
  if ($variables['id'] == 'inbox') {
    $variables['table_title'] = 'Inquiries';
  }
  else if ($variables['id'] == 'invoice') {
    $variables['table_title'] = 'Invoices';
  }
  else if ($variables['id'] == 'clients') {
    $variables['table_title'] = 'Clients';
  }
}
