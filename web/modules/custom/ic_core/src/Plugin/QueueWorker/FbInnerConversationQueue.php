<?php

namespace Drupal\ic_core\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Process pages of Facebook conversations from the API.
 *
 * @QueueWorker(
 *   id = "fb_inner_conversation_queue",
 *   title = @Translation("Process inner pages of Facebook conversations from the API"),
 *   cron = {"time" = 5}
 * )
 */
class FbInnerConversationQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($queueData) {
    $tools = \Drupal::service('ic_core.tools');
    $fbService = $tools->getFbService();
    $icFbService = \Drupal::service('ic_core.fb_service');

    $path = $queueData['path'];
    $icFacebookStorage = $queueData['icFacebookStorage'];
    $fbMessageList = $queueData['fbMessageList'];
    $clientName = $queueData['clientName'];
    $client = $queueData['client'];
    $pageAccessToken = $queueData['pageAccessToken'];

    $response = $fbService->get($path);
    $body = json_decode($response->getBody());

    if (count($body->data) > 0) {
      $icFbService->fbConversationsProcessData($body->data, $icFacebookStorage, $fbMessageList, $clientName, $client, $pageAccessToken);
    }
  }

}
