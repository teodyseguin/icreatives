<?php

namespace Drupal\ic_core;

use Drupal\ic_core\IcCoreTools;
use Facebook\Exceptions\FacebookResponseException;

/**
 * A utility class for various usage and purpose.
 */
class IcFbService {

  /**
   * @var \Drupal\ic_core\IcCoreTools
   */
  protected $tools;

  /**
   * The facebook id.
   */
  protected $fbId;

  /**
   * The facebook user access token.
   */
  protected $fbAccessToken;

  /**
   * Constructor.
   */
  public function __construct(IcCoreTools $tools) {
    $this->tools = $tools;
    $userData = $this->tools->getUserData();
    $currentUserId = $this->tools->getCurrentUser()->id();

    $this->fbId = $userData->get('ic_core', $currentUserId, 'fbid');
    $this->fbAccessToken = $userData->get('ic_core', $currentUserId, 'fb_access_token');
  }

  /**
   * Get the list of facebook pages, owned by a user.
   */
  public function getFbPages() {
    $fbService = $this->tools->getFbService();
    $fbId = $this->fbId;
    $fbAccessToken = $this->fbAccessToken;

    if (!$fbId && !$fbAccessToken) {
      return;
    }

    try {
      $response = $fbService->get("/$fbId/accounts?fields=id,name,access_token&access_token=$fbAccessToken");

      if ($response) {
        return json_decode($response->getBody());
      }
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->error('Something went wrong retrieving the facebook pages : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Get the facebook page followers count.
   */
  public function getFbPageInsights($start = NULL, $end = NULL) {
    $metric = "page_fans,page_impressions,page_impressions_organic,page_impressions_paid,page_consumptions_unique,page_fans_gender_age,page_fans_city";
    $fbService = $this->tools->getFbService();
    $pages = $this->getFbPages();
    $insights = [];
    $now = strtotime('now');
    $thirty_days_ago = strtotime('-30 days');

    if (!$pages) {
      return;
    }

    $data = $pages->data;

    if (count($data) == 0) {
      return;
    }

    $pageId = $data[0]->id;
    $pageAccessToken = $data[0]->access_token;

    try {
      $response = $fbService->get("/$pageId/insights?access_token=$pageAccessToken&metric=$metric&period=day&since=$thirty_days_ago&until=$now");
      $body = json_decode($response->getBody());

      if (count($body->data) == 0) {
        return;
      }

      foreach ($body->data as $data) {
        switch ($data->name) {
          case 'page_fans':
            $insights['total_facebook_followers'] += $this->totalFacebookFollowers($data);
          break;

          case 'page_impressions_organic':
          case 'page_impressions_paid':
            $insights['total_facebook_reach'] += $this->totalFacebookReach($data);
          break;

          case 'page_impressions':
            $insights['total_facebook_impressions'] += $this->totalFacebookImpressions($data);
          break;

          case 'page_consumptions_unique':
            $insights['link_clicks'] = $this->linkClicks($data);
          break;

          case 'post_impressions_unique':
          case 'post_engaged_users':
            // Nothing to do yet here...
          break;

          case 'page_fans_gender_age':
            $insights['gender_age_followers'] = $this->genderAgeFollowers($data);
          break;

          case 'page_fans_city':
            $insights['location_followers'] = $this->locationFollowers($data);
          break;
        }
      }

      return $insights;
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->error('Something went wrong retrieving : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Get the Top Five Contents.
   */
  public function getTopFiveContents() {
    $fbService = $this->tools->getFbService();
    $pages = $this->getFbPages();
    $popular = [];

    if (!$pages) {
      return;
    }

    $data = $pages->data;

    if (count($data) == 0) {
      return;
    }

    $pageId = $data[0]->id;
    $pageAccessToken = $data[0]->access_token;

    try {
      $response = $fbService->get("/$pageId/posts?access_token=$pageAccessToken&fields=shares,is_popular,created_time,permalink_url");
      $body = json_decode($response->getBody());

      if (count($body->data) == 0) {
        return;
      }

      foreach ($body->data as $data) {
        if ($data->is_popular == TRUE) {
          $popular[$data->shares->count] = [
            'shares' => $data->shares->count,
            'created' => date('m/d/Y', strtotime($data->created_time)),
            'link' => $data->permalink_url,
          ];
        }
      }

      // Sort in decending order.
      krsort($popular);

      // Then we only return the top 5 of them.
      return array_splice($popular, 0, 5, TRUE);
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->error('Something went wrong while retrieving your top five posts : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Parse the Page Fans Country. This will be the Total Facebook Followers.
   */
  public function totalFacebookFollowers($data) {
    return $data->values[count($data->values) - 1]->value;
  }

  /**
   * Parse the Page Impressions Unique. This will be the Total Facebook Reach.
   */
  public function totalFacebookReach($data) {
    $reach_count = 0;

    foreach ($data->values as $value) {
      $reach_count += $value->value;
    }

    return $reach_count;
  }

  /**
   * Get the Total Facebook Impressions.
   */
  public function totalFacebookImpressions($data) {
    $impressions_count = 0;

    foreach ($data->values as $value) {
      $impressions_count += $value->value;
    }

    return $impressions_count;
  }

  /**
   * Get the Link Clicks.
   */
  public function linkClicks($data) {
    $clicks_count = 0;

    foreach ($data->values as $value) {
      $clicks_count += $value->value;
    }

    return $clicks_count;
  }

  /**
   * Get the Gender and Age Followers.
   */
  public function genderAgeFollowers($data) {
    $group = [];
    $last = count($data->values) - 1;

    foreach ((array) $data->values[$last]->value as $gender_age => $count) {
      if (strpos($gender_age, 'F') !== FALSE) {
        $gender_age = str_replace('F.', 'Female, ', $gender_age) . ' yrs old';
        $group[$count] = [
          'gender_age' => $gender_age,
          'count' => $count,
        ];
      }
      elseif (strpos($gender_age, 'M') !== FALSE) {
        $gender_age = str_replace('M.', 'Male, ', $gender_age) . ' yrs old';
        $group[$count] = [
          'gender_age' => $gender_age,
          'count' => $count,
        ];
      }
    }

    krsort($group);

    return array_splice($group, 0, 3, TRUE);
  }

  /**
   * Get the Location of Followers.
   */
  public function locationFollowers($data) {
    $group = [];
    $last = count($data->values) - 1;

    foreach ((array) $data->values[$last]->value as $location => $count) {
      $group[$count] = [
        'location' => $location,
        'count' => $count,
      ];
    }

    krsort($group);

    return array_splice($group, 0, 5, TRUE);
  }

  /**
   * Validate a given access token if it is still valid.
   */
  // public function validateAccessToken($accessToken) {
  //   // debug_token?input_token=EAAeaLOjaLiwBAJCcy2DKwYfj94M52ZAsClZAEStwZB871Yp3GXXgJn4d1lVHosKcKH8eHqHaJLlRqHnVxbZCi0hXZACaUZAfXGMj0QyPMcLhMVu8T6ZBCjNxXtkBjJCGkEhZCKcxk0n1NVjr8NQwNNhCCucRcxKrAy5rP6gjnEVZBN0msHWTTV7db
  //   $response = '';

  //   if ($response->data->is_valid) {
  //     return TRUE;
  //   }

  //   return FALSE;
  // }

}
