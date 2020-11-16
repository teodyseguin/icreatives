<?php

namespace Drupal\ic_facebook\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a IC Facebook Entity revision.
 *
 * @ingroup ic_facebook
 */
class IcFacebookEntityRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The IC Facebook Entity revision.
   *
   * @var \Drupal\ic_facebook\Entity\IcFacebookEntityInterface
   */
  protected $revision;

  /**
   * The IC Facebook Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $icFacebookEntityStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->icFacebookEntityStorage = $container->get('entity_type.manager')->getStorage('ic_facebook_entity');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ic_facebook_entity_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.ic_facebook_entity.version_history', ['ic_facebook_entity' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ic_facebook_entity_revision = NULL) {
    $this->revision = $this->IcFacebookEntityStorage->loadRevision($ic_facebook_entity_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->IcFacebookEntityStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('IC Facebook Entity: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of IC Facebook Entity %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.ic_facebook_entity.canonical',
       ['ic_facebook_entity' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {ic_facebook_entity_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.ic_facebook_entity.version_history',
         ['ic_facebook_entity' => $this->revision->id()]
      );
    }
  }

}
