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
    if (count($path) > 0) {
      $path = $path['1'];
      $variables['html_attributes']->addClass($path);
    }
  }

  if ($tools->isFrontPage()) {
    $variables['html_attributes']->addClass('front');
  }

  $roles = $tools->getCurrentRoles();

  if (in_array('client', $roles)) {
    $variables['html_attributes']->addClass('client');
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function particle_preprocess_page(&$variables) {
  $variables['user_picture'] = NULL;

  // Themes are not able to declare module dependencies at this time.
  // We use this as a shortcut to guarantee the component library module exists.
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('components')) {
    $variables['has_components_module'] = TRUE;
  }

  $tools = \Drupal::service('ic_core.ic_core_tools');
  $roles = $tools->getCurrentRoles();

  // We only want to display the filter for admins.
  if (in_array('administrator', $roles) || in_array('site_admin', $roles)) {
    $variables['display_filter'] = TRUE;
  }

  $clients = $tools->getClients();
  $variables['clients'] = $clients;

  $current_user_id = $tools->getCurrentUser()->id();
  $current_user = $tools->getStorage('user')->load($current_user_id);

  $variables['full_name'] = $tools->getUserFullName();
  $variables['user_id'] = $current_user_id;

  $target_id = $current_user->user_picture->getValue();

  // Get the current user picture.
  if (!empty($target_id)) {
    $target_id = reset($target_id);
    $file = $tools->getStorage('file')->load($target_id['target_id']);

    if ($file) {
      $variables['user_picture'] = $file->get('uri')->value;
    }
  }

  $tools = \Drupal::service('ic_core.ic_core_tools');
  $ic_fb_service = \Drupal::service('ic_core.fb_service');

  if ($tools->getCurrentUser()->id() != 0 && $tools->getPath() == '/dashboard') {
    $ic_fb_service = \Drupal::service('ic_core.fb_service');
    $ic_ig_service = \Drupal::service('ic_core.ig_service');
    $client = \Drupal::request()->query->get('client');
    $from = \Drupal::request()->query->get('from');
    $to = \Drupal::request()->query->get('to');

    $variables['client'] = $client;

    // Collect the fb pages managing by this user
    $ic_fb_service->getFbPages();
    $ic_ig_service->getIgPages();

    $ic_fb_service->getFbConversations($from, $to);

    $page_insights = $ic_fb_service->getFbPageInsights($from, $to);
    $variables['page_insights'] = $page_insights;
    $variables['#attached']['drupalSettings']['ic']['page_insights'] = $page_insights;

    $fb_posts = $ic_fb_service->getFbPosts($from, $to);
    $variables['top_five_contents'] = $fb_posts['topFive'];
    $variables['engagement_rate'] = $fb_posts['engagementRate'];
    $variables['conversations'] = $ic_fb_service->createFbMessageKeywordsCount($client, $from, $to);

    $variables['ig_conversations'] = $ic_ig_service->getIgConversations($from, $to);
    $variables['ig_insights'] = $ic_ig_service->getIgPageInsights($from, $to);
    $variables['#attached']['drupalSettings']['ic']['ig_insights'] = $variables['ig_insights'];

    $user_storage = $tools->getStorage('user');
    $user_data = $tools->getUserData();
    $site_admin = $user_storage->loadByProperties(['roles' => 'site_admin']);

    if (empty($site_admin)) {
      return;
    }

    $site_admin = reset($site_admin);
    $user_id = $site_admin->id();

    $variables['#attached']['drupalSettings']['ic']['facebook_id'] = $user_data->get('ic_core', $user_id, 'fbid');
    $variables['#attached']['drupalSettings']['ic']['facebook_token'] = $user_data->get('ic_core', $user_id, 'fb_access_token');
  }
}
