<?php

namespace Drupal\ic_core;

use Drupal\ic_core\IcCoreTools;
use Facebook\Exceptions\FacebookResponseException;
use Drupal\ic_fb_pages\Entity\IcFbPageEntity;

/**
 * A utility class for various usage and purpose.
 */
class IcIgService {

  /**
   * @var \Drupal\ic_core\IcCoreTools
   */
  protected $tools;

  /**
   * This will be the same FB service being used in FB Simple Connect module.
   */
  protected $fbService;

  /**
   * Constructor.
   */
  public function __construct(IcCoreTools $tools) {
    $this->tools = $tools;
    $this->fbService = $this->tools->getFbService();
  }

  /**
   * Get the Instagram Followers count.
   */
  public function getIgFollowers() {
    $client = \Drupal::request()->query->get('client');

    if (!$client) {
      return;
    }

    $user_storage = $this->tools->getStorage('user');
    $client = $user_storage->load($client);
    $fbPageEntity = $client->field_fb_page->referencedEntities();

    if (empty($fbPageEntity)) {
      return;
    }

    $fbPageEntity = reset($fbPageEntity);
    $pageId = $fbPageEntity->get('field_page_id')->value;
    $pageAccessToken = $fbPageEntity->get('field_page_access_token')->value;

    try {
      $response = $this->fbService->get("/$pageId/instagram_accounts?fields=id,followed_by_count&access_token=$pageAccessToken");

      if ($response) {
        $this->tools->loggerFactory()
          ->get('ic_core.fb_service')
          ->error('The response has been accepted');

        $data = json_decode($response->getBody());

        if ($data) {
          return $data->data[0]->followed_by_count;
        }
      }
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.ig_service')
        ->error('Something went wrong retrieving the instagram followers count : @message', ['@message' => json_encode($e)]);
    }
  }

  public function getIgConversations($from = NULL, $to = NULL) {
    $client = \Drupal::request()->query->get('client');

    if (!$client) {
      return;
    }

    $igStorage = $this->tools->getStorage('ic_instagram');
    $query = $igStorage->getQuery();
    // $uids = $query->condition('status', '1')
    //               ->condition('roles', 'client')
    //               ->execute();
    $ids = $query->execute();
    $data = $igStorage->loadMultiple($ids);

    return $this->createKeywordsCount($data);
  }

  /**
   * Create the keywords count.
   */
  public function createKeywordsCount($conversations) {
    $client = \Drupal::request()->query->get('client');

    if (count($conversations) == 0) {
      return;
    }

    $igConversationTags = $this->getIgConversationTags();

    if (empty($igConversationTags)) {
      return;
    }

    foreach ($conversations as $conversation) {
      $referencedClient = $conversation->field_client->referencedEntities();
      $term = $conversation->field_instagram_conversation_tag->referencedEntities();

      if (empty($term)) {
        continue;
      }

      if (empty($referencedClient)) {
        continue;
      }

      $referencedClient = reset($referencedClient);

      if ($referencedClient->id() != $client) {
        continue;
      }

      $term = reset($term);
      $term = $term->get('name')->value;
      $igConversationTags[$term]['total'] += $conversation->get('field_count')->value;
    }

    return $igConversationTags;
  }

  /**
   * Get the IG Conversation tags.
   */
  function getIgConversationTags() {
    $vid = 'ig_conversation_tags';
    $termStorage = $this->tools->getStorage('taxonomy_term');
    $terms = $termStorage->loadTree($vid);
    $termsData = [];

    if (count($terms) == 0) {
      return;
    }

    foreach ($terms as $term) {
      $name = str_replace('#', '', $term->name);
      $name = explode('_', $name);
      $name = implode(' ', $name);
      $termsData[$term->name] = [
        'name' => ucwords($name),
        'total' => 0,
      ];
    }

    return $termsData;
  }

}
