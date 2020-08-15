<?php

/**
 * @file
 * user_login.inc
 */

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements hook_user_login().
 */
function ic_core_user_login() {
  $tools = \Drupal::service('ic_core.ic_core_tools');
  $path_alias = $tools->getAliasByPath("/splash");
  $response = new RedirectResponse($path_alias);
  $request = \Drupal::request();
  $request->getSession()->save();
  $response->prepare($request);
  \Drupal::service('kernel')->terminate($request, $response);
  $response->send();
}
