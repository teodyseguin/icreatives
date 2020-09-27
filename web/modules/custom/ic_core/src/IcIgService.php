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

}
