<?php

namespace Drupal\ic_instagram\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining IC Instagram entities.
 *
 * @ingroup ic_instagram
 */
interface IcInstagramInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the IC Instagram name.
   *
   * @return string
   *   Name of the IC Instagram.
   */
  public function getName();

  /**
   * Sets the IC Instagram name.
   *
   * @param string $name
   *   The IC Instagram name.
   *
   * @return \Drupal\ic_instagram\Entity\IcInstagramInterface
   *   The called IC Instagram entity.
   */
  public function setName($name);

  /**
   * Gets the IC Instagram creation timestamp.
   *
   * @return int
   *   Creation timestamp of the IC Instagram.
   */
  public function getCreatedTime();

  /**
   * Sets the IC Instagram creation timestamp.
   *
   * @param int $timestamp
   *   The IC Instagram creation timestamp.
   *
   * @return \Drupal\ic_instagram\Entity\IcInstagramInterface
   *   The called IC Instagram entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the IC Instagram revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the IC Instagram revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\ic_instagram\Entity\IcInstagramInterface
   *   The called IC Instagram entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the IC Instagram revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the IC Instagram revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\ic_instagram\Entity\IcInstagramInterface
   *   The called IC Instagram entity.
   */
  public function setRevisionUserId($uid);

}
