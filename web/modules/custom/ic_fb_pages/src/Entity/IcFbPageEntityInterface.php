<?php

namespace Drupal\ic_fb_pages\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ic fb page entity entities.
 *
 * @ingroup ic_fb_pages
 */
interface IcFbPageEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ic fb page entity name.
   *
   * @return string
   *   Name of the Ic fb page entity.
   */
  public function getName();

  /**
   * Sets the Ic fb page entity name.
   *
   * @param string $name
   *   The Ic fb page entity name.
   *
   * @return \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface
   *   The called Ic fb page entity entity.
   */
  public function setName($name);

  /**
   * Gets the Ic fb page entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ic fb page entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Ic fb page entity creation timestamp.
   *
   * @param int $timestamp
   *   The Ic fb page entity creation timestamp.
   *
   * @return \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface
   *   The called Ic fb page entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Ic fb page entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Ic fb page entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface
   *   The called Ic fb page entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Ic fb page entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Ic fb page entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface
   *   The called Ic fb page entity entity.
   */
  public function setRevisionUserId($uid);

}
