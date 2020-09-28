<?php

namespace Drupal\ic_instagram\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\ic_instagram\Entity\IcInstagramInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IcInstagramController.
 *
 *  Returns responses for IC Instagram routes.
 */
class IcInstagramController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a IC Instagram revision.
   *
   * @param int $ic_instagram_revision
   *   The IC Instagram revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($ic_instagram_revision) {
    $ic_instagram = $this->entityTypeManager()->getStorage('ic_instagram')
      ->loadRevision($ic_instagram_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('ic_instagram');

    return $view_builder->view($ic_instagram);
  }

  /**
   * Page title callback for a IC Instagram revision.
   *
   * @param int $ic_instagram_revision
   *   The IC Instagram revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($ic_instagram_revision) {
    $ic_instagram = $this->entityTypeManager()->getStorage('ic_instagram')
      ->loadRevision($ic_instagram_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $ic_instagram->label(),
      '%date' => $this->dateFormatter->format($ic_instagram->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a IC Instagram.
   *
   * @param \Drupal\ic_instagram\Entity\IcInstagramInterface $ic_instagram
   *   A IC Instagram object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(IcInstagramInterface $ic_instagram) {
    $account = $this->currentUser();
    $ic_instagram_storage = $this->entityTypeManager()->getStorage('ic_instagram');

    $langcode = $ic_instagram->language()->getId();
    $langname = $ic_instagram->language()->getName();
    $languages = $ic_instagram->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $ic_instagram->label()]) : $this->t('Revisions for %title', ['%title' => $ic_instagram->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all ic instagram revisions") || $account->hasPermission('administer ic instagram entities')));
    $delete_permission = (($account->hasPermission("delete all ic instagram revisions") || $account->hasPermission('administer ic instagram entities')));

    $rows = [];

    $vids = $ic_instagram_storage->revisionIds($ic_instagram);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\ic_instagram\IcInstagramInterface $revision */
      $revision = $ic_instagram_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $ic_instagram->getRevisionId()) {
          $link = $this->l($date, new Url('entity.ic_instagram.revision', [
            'ic_instagram' => $ic_instagram->id(),
            'ic_instagram_revision' => $vid,
          ]));
        }
        else {
          $link = $ic_instagram->link($date);
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
              Url::fromRoute('entity.ic_instagram.translation_revert', [
                'ic_instagram' => $ic_instagram->id(),
                'ic_instagram_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.ic_instagram.revision_revert', [
                'ic_instagram' => $ic_instagram->id(),
                'ic_instagram_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.ic_instagram.revision_delete', [
                'ic_instagram' => $ic_instagram->id(),
                'ic_instagram_revision' => $vid,
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

    $build['ic_instagram_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
