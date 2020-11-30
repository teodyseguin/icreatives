<?php

namespace Drupal\ic_core\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Process pages of Facebook conversations from the API.
 *
 * @QueueWorker(
 *   id = "fb_conversation_queue",
 *   title = @Translation("Process pages of Facebook conversations from the API"),
 *   cron = {"time" = 5}
 * )
 */
class FbConversationQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($after) {
    $icFbService = \Drupal::service('ic_core.fb_service');
    $icFbService->getFbConversations(NULL, NULL, NULL, $after);
  }

}
