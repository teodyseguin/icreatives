<?php

namespace Drupal\ic_facebook\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for IC Facebook Entity entities.
 */
class IcFacebookEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
