<?php

namespace Drupal\ic_fb_pages;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface;

/**
 * Defines the storage handler class for Ic fb page entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Ic fb page entity entities.
 *
 * @ingroup ic_fb_pages
 */
class IcFbPageEntityStorage extends SqlContentEntityStorage implements IcFbPageEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(IcFbPageEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {ic_fb_page_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {ic_fb_page_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(IcFbPageEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {ic_fb_page_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('ic_fb_page_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
