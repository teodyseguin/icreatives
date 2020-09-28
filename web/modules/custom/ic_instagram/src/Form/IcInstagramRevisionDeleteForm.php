<?php

namespace Drupal\ic_instagram\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a IC Instagram revision.
 *
 * @ingroup ic_instagram
 */
class IcInstagramRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The IC Instagram revision.
   *
   * @var \Drupal\ic_instagram\Entity\IcInstagramInterface
   */
  protected $revision;

  /**
   * The IC Instagram storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $icInstagramStorage;

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
    $instance->icInstagramStorage = $container->get('entity_type.manager')->getStorage('ic_instagram');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ic_instagram_revision_delete_confirm';
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
    return new Url('entity.ic_instagram.version_history', ['ic_instagram' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $ic_instagram_revision = NULL) {
    $this->revision = $this->IcInstagramStorage->loadRevision($ic_instagram_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->IcInstagramStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('IC Instagram: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of IC Instagram %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.ic_instagram.canonical',
       ['ic_instagram' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {ic_instagram_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.ic_instagram.version_history',
         ['ic_instagram' => $this->revision->id()]
      );
    }
  }

}
