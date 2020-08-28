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

  if ($form_id == 'comment_messages_form') {
    // Custom style library to style the comment form from the messages content type.
    $form['#attached']['library'][] = 'particle/form.comment';
    // Change the label of the submit button.
    $form['actions']['submit']['#value'] = 'SEND';
    // We don't need to use the preview button so we are disabling it from here.
    $form['actions']['preview']['#access'] = FALSE;
  }
}
