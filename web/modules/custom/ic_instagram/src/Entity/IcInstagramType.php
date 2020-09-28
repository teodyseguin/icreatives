<?php

namespace Drupal\ic_instagram\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the IC Instagram type entity.
 *
 * @ConfigEntityType(
 *   id = "ic_instagram_type",
 *   label = @Translation("IC Instagram type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ic_instagram\IcInstagramTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ic_instagram\Form\IcInstagramTypeForm",
 *       "edit" = "Drupal\ic_instagram\Form\IcInstagramTypeForm",
 *       "delete" = "Drupal\ic_instagram\Form\IcInstagramTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ic_instagram\IcInstagramTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ic_instagram_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ic_instagram",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ic_instagram_type/{ic_instagram_type}",
 *     "add-form" = "/admin/structure/ic_instagram_type/add",
 *     "edit-form" = "/admin/structure/ic_instagram_type/{ic_instagram_type}/edit",
 *     "delete-form" = "/admin/structure/ic_instagram_type/{ic_instagram_type}/delete",
 *     "collection" = "/admin/structure/ic_instagram_type"
 *   }
 * )
 */
class IcInstagramType extends ConfigEntityBundleBase implements IcInstagramTypeInterface {

  /**
   * The IC Instagram type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The IC Instagram type label.
   *
   * @var string
   */
  protected $label;

}
