<?php

/**
 * @file
 * form_alter.inc
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Impements hook_form_alter().
 */
function ic_core_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form_id == 'user_login_form') {
    $form['#attached']['library'][] = 'particle/form.user_login';
    $form['name']['#attributes']['placeholder'] = 'Enter Email Address...';
    $form['pass']['#attributes']['placeholder'] = 'Password';
  }
}
