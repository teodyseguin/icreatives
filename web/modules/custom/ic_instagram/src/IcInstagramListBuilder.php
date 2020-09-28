<?php

namespace Drupal\ic_instagram;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of IC Instagram entities.
 *
 * @ingroup ic_instagram
 */
class IcInstagramListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('IC Instagram ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ic_instagram\Entity\IcInstagram $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ic_instagram.edit_form',
      ['ic_instagram' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
