<?php

namespace Drupal\ic_facebook\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining IC Facebook Entity entities.
 *
 * @ingroup ic_facebook
 */
interface IcFacebookEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the IC Facebook Entity name.
   *
   * @return string
   *   Name of the IC Facebook Entity.
   */
  public function getName();

  /**
   * Sets the IC Facebook Entity name.
   *
   * @param string $name
   *   The IC Facebook Entity name.
   *
   * @return \Drupal\ic_facebook\Entity\IcFacebookEntityInterface
   *   The called IC Facebook Entity entity.
   */
  public function setName($name);

  /**
   * Gets the IC Facebook Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the IC Facebook Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the IC Facebook Entity creation timestamp.
   *
   * @param int $timestamp
   *   The IC Facebook Entity creation timestamp.
   *
   * @return \Drupal\ic_facebook\Entity\IcFacebookEntityInterface
   *   The called IC Facebook Entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the IC Facebook Entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the IC Facebook Entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\ic_facebook\Entity\IcFacebookEntityInterface
   *   The called IC Facebook Entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the IC Facebook Entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the IC Facebook Entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\ic_facebook\Entity\IcFacebookEntityInterface
   *   The called IC Facebook Entity entity.
   */
  public function setRevisionUserId($uid);

}
