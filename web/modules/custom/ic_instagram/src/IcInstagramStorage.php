<?php

namespace Drupal\ic_instagram;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ic_instagram\Entity\IcInstagramInterface;

/**
 * Defines the storage handler class for IC Instagram entities.
 *
 * This extends the base storage class, adding required special handling for
 * IC Instagram entities.
 *
 * @ingroup ic_instagram
 */
class IcInstagramStorage extends SqlContentEntityStorage implements IcInstagramStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(IcInstagramInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {ic_instagram_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {ic_instagram_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(IcInstagramInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {ic_instagram_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('ic_instagram_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
