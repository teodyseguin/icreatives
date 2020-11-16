<?php

namespace Drupal\ic_facebook;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ic_facebook\Entity\IcFacebookEntityInterface;

/**
 * Defines the storage handler class for IC Facebook Entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * IC Facebook Entity entities.
 *
 * @ingroup ic_facebook
 */
class IcFacebookEntityStorage extends SqlContentEntityStorage implements IcFacebookEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(IcFacebookEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {ic_facebook_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {ic_facebook_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(IcFacebookEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {ic_facebook_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('ic_facebook_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
