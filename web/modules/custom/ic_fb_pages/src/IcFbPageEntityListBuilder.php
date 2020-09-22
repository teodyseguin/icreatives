<?php

namespace Drupal\ic_fb_pages;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ic fb page entity entities.
 *
 * @ingroup ic_fb_pages
 */
class IcFbPageEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ic fb page entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ic_fb_pages\Entity\IcFbPageEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ic_fb_page_entity.edit_form',
      ['ic_fb_page_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
