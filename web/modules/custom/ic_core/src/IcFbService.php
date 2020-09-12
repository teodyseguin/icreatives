<?php

namespace Drupal\ic_core;

use Drupal\ic_core\IcCoreTools;

/**
 * A utility class for various usage and purpose.
 */
class IcFbService {

  protected $tools;
  protected $fbId;
  protected $fbAccessToken;

  public function __construct(IcCoreTools $tools) {
    $this->tools = $tools;
    $userData = $this->tools->getUserData();
    $currentUserId = $this->tools->getCurrentUser()->id();

    $this->fbId = $userData->get('ic_core', $currentUserId, 'fbid');
    $this->fbAccessToken = $userData->get('ic_core', $currentUserId, 'fb_access_token');
  }

  public function getFbPages() {
    $fbService = $this->tools->getFbService();
    $fbId = $this->fbId;
    $fbAccessToken = $this->fbAccessToken;
    $response = $fbService->get("/$fbId/accounts?fields=id,name,access_token&access_token=$fbAccessToken");

    if ($response) {
      return json_decode($response->getBody());
    }
  }

  /**
   * Get the facebook page followers count.
   */
  public function getFbPageFollowers() {
    $fbService = $this->tools->getFbService();
    $pages = $this->getFbPages();
    $count = 0;
    $pageAccessToken = NULL;
    $pageId = NULL;

    if ($pages) {
      $data = $pages->data;

      if (count($data) != 0) {
        foreach ($data as $page) {
          $pageAccessToken = $page->access_token;
          $pageId = $page->id;
        }
      }

      if ($pageId && $pageAccessToken) {
        $response = $fbService->get("/$pageId/insights/page_fans_country?access_token=$pageAccessToken");
        $response = json_decode($response);

        // foreach ($response->data->values as $value) {
        //   $count += $value;
        // }
      }
    }

    // return $count;
  }

}
