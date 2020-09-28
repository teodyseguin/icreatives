<?php

namespace Drupal\ic_instagram;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface IcInstagramStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of IC Instagram revision IDs for a specific IC Instagram.
   *
   * @param \Drupal\ic_instagram\Entity\IcInstagramInterface $entity
   *   The IC Instagram entity.
   *
   * @return int[]
   *   IC Instagram revision IDs (in ascending order).
   */
  public function revisionIds(IcInstagramInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as IC Instagram author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   IC Instagram revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\ic_instagram\Entity\IcInstagramInterface $entity
   *   The IC Instagram entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(IcInstagramInterface $entity);

  /**
   * Unsets the language for all IC Instagram with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
