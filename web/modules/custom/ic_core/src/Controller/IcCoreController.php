<?php

namespace Drupal\ic_core\Controller;

use Drupal\Core\Controller\ControllerBase;

class IcCoreController extends ControllerBase {

  /**
   * Creates the splash screen content.
   */
  public function splash() {
    return [
      '#theme' => 'splash_screen_content',
    ];
  }

  /**
   * Creates the Dashboard page.
   */
  public function dashboard() {
    return [
      '#theme' => 'dashboard',
    ];
  }

}
