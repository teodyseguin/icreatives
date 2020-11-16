<?php

namespace Drupal\ic_facebook;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface IcFacebookEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of IC Facebook Entity revision IDs for a specific IC Facebook Entity.
   *
   * @param \Drupal\ic_facebook\Entity\IcFacebookEntityInterface $entity
   *   The IC Facebook Entity entity.
   *
   * @return int[]
   *   IC Facebook Entity revision IDs (in ascending order).
   */
  public function revisionIds(IcFacebookEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as IC Facebook Entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   IC Facebook Entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\ic_facebook\Entity\IcFacebookEntityInterface $entity
   *   The IC Facebook Entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(IcFacebookEntityInterface $entity);

  /**
   * Unsets the language for all IC Facebook Entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
