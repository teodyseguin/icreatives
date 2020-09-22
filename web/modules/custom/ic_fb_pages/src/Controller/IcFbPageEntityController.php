<?php

namespace Drupal\ic_fb_pages\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IcFbPageEntityController.
 *
 *  Returns responses for Ic fb page entity routes.
 */
class IcFbPageEntityController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a Ic fb page entity revision.
   *
   * @param int $ic_fb_page_entity_revision
   *   The Ic fb page entity revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($ic_fb_page_entity_revision) {
    $ic_fb_page_entity = $this->entityTypeManager()->getStorage('ic_fb_page_entity')
      ->loadRevision($ic_fb_page_entity_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('ic_fb_page_entity');

    return $view_builder->view($ic_fb_page_entity);
  }

  /**
   * Page title callback for a Ic fb page entity revision.
   *
   * @param int $ic_fb_page_entity_revision
   *   The Ic fb page entity revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($ic_fb_page_entity_revision) {
    $ic_fb_page_entity = $this->entityTypeManager()->getStorage('ic_fb_page_entity')
      ->loadRevision($ic_fb_page_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $ic_fb_page_entity->label(),
      '%date' => $this->dateFormatter->format($ic_fb_page_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Ic fb page entity.
   *
   * @param \Drupal\ic_fb_pages\Entity\IcFbPageEntityInterface $ic_fb_page_entity
   *   A Ic fb page entity object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(IcFbPageEntityInterface $ic_fb_page_entity) {
    $account = $this->currentUser();
    $ic_fb_page_entity_storage = $this->entityTypeManager()->getStorage('ic_fb_page_entity');

    $langcode = $ic_fb_page_entity->language()->getId();
    $langname = $ic_fb_page_entity->language()->getName();
    $languages = $ic_fb_page_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $ic_fb_page_entity->label()]) : $this->t('Revisions for %title', ['%title' => $ic_fb_page_entity->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all ic fb page entity revisions") || $account->hasPermission('administer ic fb page entity entities')));
    $delete_permission = (($account->hasPermission("delete all ic fb page entity revisions") || $account->hasPermission('administer ic fb page entity entities')));

    $rows = [];

    $vids = $ic_fb_page_entity_storage->revisionIds($ic_fb_page_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\ic_fb_pages\IcFbPageEntityInterface $revision */
      $revision = $ic_fb_page_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $ic_fb_page_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.ic_fb_page_entity.revision', [
            'ic_fb_page_entity' => $ic_fb_page_entity->id(),
            'ic_fb_page_entity_revision' => $vid,
          ]));
        }
        else {
          $link = $ic_fb_page_entity->link($date);
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
              Url::fromRoute('entity.ic_fb_page_entity.translation_revert', [
                'ic_fb_page_entity' => $ic_fb_page_entity->id(),
                'ic_fb_page_entity_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.ic_fb_page_entity.revision_revert', [
                'ic_fb_page_entity' => $ic_fb_page_entity->id(),
                'ic_fb_page_entity_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.ic_fb_page_entity.revision_delete', [
                'ic_fb_page_entity' => $ic_fb_page_entity->id(),
                'ic_fb_page_entity_revision' => $vid,
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

    $build['ic_fb_page_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
