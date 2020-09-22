<?php

namespace Drupal\ic_fb_pages\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a Ic fb page entity revision.
 *
 * @ingroup ic_fb_pages
 */
class IcFbPageEntityRevisionRevertForm extends ConfirmFormBase {

  /**
   * The Ic fb page entity revision.
   *
   * @var \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface
   */
  protected $revision;

  /**
   * The Ic fb page entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $icFbPageEntityStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->icFbPageEntityStorage = $container->get('entity_type.manager')->getStorage('ic_fb_page_entity');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ic_fb_page_entity_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert to the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.ic_fb_page_entity.version_history', ['ic_fb_page_entity' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ic_fb_page_entity_revision = NULL) {
    $this->revision = $this->IcFbPageEntityStorage->loadRevision($ic_fb_page_entity_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = $this->t('Copy of the revision from %date.', [
      '%date' => $this->dateFormatter->format($original_revision_timestamp),
    ]);
    $this->revision->save();

    $this->logger('content')->notice('Ic fb page entity: reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Ic fb page entity %title has been reverted to the revision from %revision-date.', ['%title' => $this->revision->label(), '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)]));
    $form_state->setRedirect(
      'entity.ic_fb_page_entity.version_history',
      ['ic_fb_page_entity' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(IcFbPageEntityInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime(REQUEST_TIME);

    return $revision;
  }

}
