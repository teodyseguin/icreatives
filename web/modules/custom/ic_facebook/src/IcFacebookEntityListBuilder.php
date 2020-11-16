<?php

namespace Drupal\ic_facebook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of IC Facebook Entity entities.
 *
 * @ingroup ic_facebook
 */
class IcFacebookEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('IC Facebook Entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ic_facebook\Entity\IcFacebookEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ic_facebook_entity.edit_form',
      ['ic_facebook_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
