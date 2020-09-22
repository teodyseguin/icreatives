<?php

namespace Drupal\ic_fb_pages;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ic fb page entity entity.
 *
 * @see \Drupal\ic_fb_pages\Entity\IcFbPageEntity.
 */
class IcFbPageEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ic fb page entity entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published ic fb page entity entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit ic fb page entity entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete ic fb page entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ic fb page entity entities');
  }


}
