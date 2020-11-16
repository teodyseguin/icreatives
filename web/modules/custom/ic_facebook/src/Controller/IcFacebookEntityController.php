<?php

namespace Drupal\ic_facebook\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\ic_facebook\Entity\IcFacebookEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IcFacebookEntityController.
 *
 *  Returns responses for IC Facebook Entity routes.
 */
class IcFacebookEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a IC Facebook Entity revision.
   *
   * @param int $ic_facebook_entity_revision
   *   The IC Facebook Entity revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($ic_facebook_entity_revision) {
    $ic_facebook_entity = $this->entityTypeManager()->getStorage('ic_facebook_entity')
      ->loadRevision($ic_facebook_entity_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('ic_facebook_entity');

    return $view_builder->view($ic_facebook_entity);
  }

  /**
   * Page title callback for a IC Facebook Entity revision.
   *
   * @param int $ic_facebook_entity_revision
   *   The IC Facebook Entity revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($ic_facebook_entity_revision) {
    $ic_facebook_entity = $this->entityTypeManager()->getStorage('ic_facebook_entity')
      ->loadRevision($ic_facebook_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $ic_facebook_entity->label(),
      '%date' => $this->dateFormatter->format($ic_facebook_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a IC Facebook Entity.
   *
   * @param \Drupal\ic_facebook\Entity\IcFacebookEntityInterface $ic_facebook_entity
   *   A IC Facebook Entity object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(IcFacebookEntityInterface $ic_facebook_entity) {
    $account = $this->currentUser();
    $ic_facebook_entity_storage = $this->entityTypeManager()->getStorage('ic_facebook_entity');

    $langcode = $ic_facebook_entity->language()->getId();
    $langname = $ic_facebook_entity->language()->getName();
    $languages = $ic_facebook_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $ic_facebook_entity->label()]) : $this->t('Revisions for %title', ['%title' => $ic_facebook_entity->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all ic facebook entity revisions") || $account->hasPermission('administer ic facebook entity entities')));
    $delete_permission = (($account->hasPermission("delete all ic facebook entity revisions") || $account->hasPermission('administer ic facebook entity entities')));

    $rows = [];

    $vids = $ic_facebook_entity_storage->revisionIds($ic_facebook_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\ic_facebook\IcFacebookEntityInterface $revision */
      $revision = $ic_facebook_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $ic_facebook_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.ic_facebook_entity.revision', [
            'ic_facebook_entity' => $ic_facebook_entity->id(),
            'ic_facebook_entity_revision' => $vid,
          ]));
        }
        else {
          $link = $ic_facebook_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.ic_facebook_entity.translation_revert', [
                'ic_facebook_entity' => $ic_facebook_entity->id(),
                'ic_facebook_entity_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.ic_facebook_entity.revision_revert', [
                'ic_facebook_entity' => $ic_facebook_entity->id(),
                'ic_facebook_entity_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.ic_facebook_entity.revision_delete', [
                'ic_facebook_entity' => $ic_facebook_entity->id(),
                'ic_facebook_entity_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['ic_facebook_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
