<?php

namespace Drupal\ic_instagram\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IcInstagramTypeForm.
 */
class IcInstagramTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ic_instagram_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ic_instagram_type->label(),
      '#description' => $this->t("Label for the IC Instagram type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ic_instagram_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ic_instagram\Entity\IcInstagramType::load',
      ],
      '#disabled' => !$ic_instagram_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ic_instagram_type = $this->entity;
    $status = $ic_instagram_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label IC Instagram type.', [
          '%label' => $ic_instagram_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label IC Instagram type.', [
          '%label' => $ic_instagram_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($ic_instagram_type->toUrl('collection'));
  }

}
