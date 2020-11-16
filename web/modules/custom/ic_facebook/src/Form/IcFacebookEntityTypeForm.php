<?php

namespace Drupal\ic_facebook\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IcFacebookEntityTypeForm.
 */
class IcFacebookEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ic_facebook_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ic_facebook_entity_type->label(),
      '#description' => $this->t("Label for the IC Facebook Entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ic_facebook_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ic_facebook\Entity\IcFacebookEntityType::load',
      ],
      '#disabled' => !$ic_facebook_entity_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ic_facebook_entity_type = $this->entity;
    $status = $ic_facebook_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label IC Facebook Entity type.', [
          '%label' => $ic_facebook_entity_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label IC Facebook Entity type.', [
          '%label' => $ic_facebook_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($ic_facebook_entity_type->toUrl('collection'));
  }

}
