<?php

namespace Drupal\ic_fb_pages;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface IcFbPageEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Ic fb page entity revision IDs for a specific Ic fb page entity.
   *
   * @param \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface $entity
   *   The Ic fb page entity entity.
   *
   * @return int[]
   *   Ic fb page entity revision IDs (in ascending order).
   */
  public function revisionIds(IcFbPageEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Ic fb page entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Ic fb page entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface $entity
   *   The Ic fb page entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(IcFbPageEntityInterface $entity);

  /**
   * Unsets the language for all Ic fb page entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
