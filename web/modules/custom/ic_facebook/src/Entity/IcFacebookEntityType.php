<?php

namespace Drupal\ic_facebook\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the IC Facebook Entity type entity.
 *
 * @ConfigEntityType(
 *   id = "ic_facebook_entity_type",
 *   label = @Translation("IC Facebook Entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ic_facebook\IcFacebookEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ic_facebook\Form\IcFacebookEntityTypeForm",
 *       "edit" = "Drupal\ic_facebook\Form\IcFacebookEntityTypeForm",
 *       "delete" = "Drupal\ic_facebook\Form\IcFacebookEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ic_facebook\IcFacebookEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ic_facebook_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ic_facebook_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ic_facebook_entity_type/{ic_facebook_entity_type}",
 *     "add-form" = "/admin/structure/ic_facebook_entity_type/add",
 *     "edit-form" = "/admin/structure/ic_facebook_entity_type/{ic_facebook_entity_type}/edit",
 *     "delete-form" = "/admin/structure/ic_facebook_entity_type/{ic_facebook_entity_type}/delete",
 *     "collection" = "/admin/structure/ic_facebook_entity_type"
 *   }
 * )
 */
class IcFacebookEntityType extends ConfigEntityBundleBase implements IcFacebookEntityTypeInterface {

  /**
   * The IC Facebook Entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The IC Facebook Entity type label.
   *
   * @var string
   */
  protected $label;

}
