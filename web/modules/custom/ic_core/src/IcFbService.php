<?php

namespace Drupal\ic_core;

use Drupal\ic_core\IcCoreTools;
use Facebook\Exceptions\FacebookResponseException;
use Drupal\ic_fb_pages\Entity\IcFbPageEntity;

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
   * This will be the same FB service being used in FB Simple Connect module.
   */
  protected $fbService;

  /**
   * Constructor.
   */
  public function __construct(IcCoreTools $tools) {
    $this->tools = $tools;
    $this->fbService = $tools->getFbService();
    $userData = $this->tools->getUserData();
    $userStorage = $this->tools->getStorage('user');
    $siteAdmin = $userStorage->loadByProperties(['roles' => 'site_admin']);

    if (empty($siteAdmin)) {
      return;
    }

    $siteAdmin = reset($siteAdmin);
    $userId = $siteAdmin->id();

    $this->fbId = $userData->get('ic_core', $userId, 'fbid');
    $this->fbAccessToken = $userData->get('ic_core', $userId, 'fb_access_token');
  }

  /**
   * Get the list of facebook pages, owned by a user.
   */
  public function getFbPages() {
    $fbId = $this->fbId;
    $fbAccessToken = $this->fbAccessToken;

    if (!$fbId && !$fbAccessToken) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->notice('Facebook ID and Access token is not available.');

      return;
    }

    try {
      $response = $this->fbService->get("/$fbId/accounts?fields=id,name,access_token&access_token=$fbAccessToken");

      if ($response) {
        $this->tools->loggerFactory()
          ->get('ic_core.fb_service')
          ->notice('The response has been accepted');
        
        $pages = json_decode($response->getBody());
        $this->upsertFbPages($pages);

        return $pages;
      }
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->error('Something went wrong retrieving the facebook pages : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Create/Update FB Page Entities.
   *
   * @var $pages
   *   An array of Facebook pages with id, name and access token.
   */
  public function upsertFbPages($pages) {
    if (!$pages && !$pages->data && count($pages->data) == 0) {
      return;
    }

    // Fetch all FB Page Entities.
    $icFbPageEntityStorage = $this->tools->getStorage('ic_fb_page_entity');

    foreach ($pages->data as $data) {
      $fbPage = $icFbPageEntityStorage->loadByProperties(['field_page_id' => $data->id]);

      if (empty($fbPage)) {
        $fbPageEntity = IcFbPageEntity::create([
          'name' => $data->name,
          'field_page_id' => $data->id,
          'field_page_access_token' => (string) $data->access_token,
        ]);
  
        $fbPageEntity->save();
      }
      else {
        // If FB Page Entity already exists,
        // then we just wanna make sure the access token is updated.
        $fbPage = reset($fbPage);
        $fbPage->set('field_page_access_token', (string) $data->access_token);
        $fbPage->save();
      }
    }
  }

  /**
   * Get the facebook page followers count.
   */
  public function getFbPageInsights($from = NULL, $to = NULL) {
    $metric = "page_fans,page_impressions,page_impressions_organic,page_impressions_paid,page_consumptions_unique,page_fans_gender_age,page_fans_city";
    $client = \Drupal::request()->query->get('client');
    $insights = [
      'total_facebook_followers' => 0,
      'total_facebook_reach' => 0,
      'total_facebook_impressions' => 0,
      'link_clicks' => 0,
      'gender_age_followers' => 0,
      'location_followers' => 0,
    ];
    $until = $to ? $to : strtotime('now');
    $since = $from ? $from : strtotime('-30 days');

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
      $response = $this->fbService->get("/$pageId/insights?access_token=$pageAccessToken&metric=$metric&period=day&since=$since&until=$until");
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
  public function getFbPosts($from = NULL, $to = NULL) {
    $client = \Drupal::request()->query->get('client');
    $popular = [];
    $until = $to ? $to : strtotime('now');
    $since = $from ? $from : strtotime('-30 days');

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
      $response = $this->fbService->get("/$pageId/posts?access_token=$pageAccessToken&fields=id,message,picture,shares,is_popular,created_time,permalink_url&since=$since&until=$until");
      $body = json_decode($response->getBody());

      if (count($body->data) == 0) {
        return;
      }

      $postIds = [];

      foreach ($body->data as $data) {
        $postIds[] = $data->id;

        if ($data->is_popular == TRUE) {
          $popular[$data->shares->count] = [
            'shares' => $data->shares->count,
            'created' => date('m/d/Y', strtotime($data->created_time)),
            'link' => $data->permalink_url,
            'picture' => $data->picture,
            'message' => substr($data->message, 0, 30) . '...',
          ];
        }
      }

      // Sort in decending order.
      krsort($popular);

      // Then we only return the top 5 of them.
      return [
        'topFive' => array_splice($popular, 0, 5, TRUE),
        'engagementRate' => $this->getEngagementRate($postIds, $pageAccessToken, $since, $until),
      ];
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->error('Something went wrong while retrieving your top five posts : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Get the engagement rate.
   */
  public function getEngagementRate($postIds, $pageAccessToken, $since, $until) {
    $postsData = [];

    foreach ($postIds as $postId) {
      $path = "/$postId/insights?access_token=$pageAccessToken&metric=post_impressions_unique,post_engaged_users&since=$since&until=$until";

      try {
        $response = $this->fbService->get($path);
        $body = json_decode($response->getBody());
        $postImpressionsUniqueCount = 0;
        $postEngagedUsersCount = 0;

        if (count($body->data) == 0) {
          return;
        }

        foreach ($body->data as $data) {
          $postsData[$data->name][$postId][] = $data->values[0]->value;
          switch ($data->name) {
            case 'post_impressions_unique':
              $postImpressionsUniqueCount += $data->values[0]->value;
            break;

            case 'post_engaged_users':
              $postEngagedUsersCount += $data->values[0]->value;
            break;
          }
        }

        return ($postEngagedUsersCount/$postImpressionsUniqueCount) * 100;
      }
      catch (FacebookResponseException $e) {
        $this->tools->loggerFactory()
          ->get('ic_core.fb_service')
          ->error('Something went wrong while retrieving post: @postid engagement rate : @message', [
            '@postid' => $postId,
            '@message' => json_encode($e),
          ]);
      }
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
   * Get the Facebook Conversations.
   */
  public function getFbConversations($from = NULL, $to = NULL) {
    $client = \Drupal::request()->query->get('client');
    $until = $to ? $to : strtotime('now');
    $since = $from ? $from : strtotime('-30 days');
    $conversations = [];

    if (!$client) {
      return;
    }

    $pageId = $this->getClientPageId($client);
    $pageAccessToken = $this->getClientPageAccessToken($client);

    try {
      $response = $this->fbService->get("/$pageId/conversations?access_token=$pageAccessToken&fields=messages{message}&since=$since&until=$until");
      $body = json_decode($response->getBody());

      if (count($body->data) == 0) {
        return;
      }

      // Let's try to collect first all the messages.
      foreach ($body->data as $data) {
        if (count($data->messages->data) == 0) {
          continue;
        }

        foreach ($data->messages->data as $message) {
          $conversations[] = $message;
        }
      }

      return $this->createKeywordsCount($conversations);
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->error('Something went wrong while retrieving the conversations : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Create the keywords count.
   */
  public function createKeywordsCount($conversations) {
    if (count($conversations) == 0) {
      return;
    }

    $conversationTags = $this->getConversationTags();

    if (empty($conversationTags)) {
      return;
    }

    $findHash = function($carry, $item) {
      if (strpos($item, '#') !== FALSE) {
        return $carry = $item;
      }
    };

    $tagsFound = 0;

    foreach ($conversations as $conversation) {
      $chunks = explode(' ', $conversation->message);
      $found = array_reduce($chunks, $findHash);

      if ($found != NULL && array_key_exists($found, $conversationTags)) {
        $tagsFound++;
        $conversationTags[$found]['total'] += 1;
      }
    }

    if ($tagsFound != 0) {
      foreach ($conversationTags as $key => $tag) {
        if ($tag['total'] == 0) {
          unset($conversationTags[$key]);
        }
      }

      return $conversationTags;
    }
  }

  /**
   * Get the list of Conversation Tags and return a data array of it.
   */
  public function getConversationTags() {
    $vid = 'conversation_tags';
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

  /**
   * Get the client's stored FB page ID.
   */
  public function getClientPageId($client) {
    $user_storage = $this->tools->getStorage('user');
    $client = $user_storage->load($client);
    $fbPageEntity = $client->field_fb_page->referencedEntities();

    if (empty($fbPageEntity)) {
      return;
    }

    $fbPageEntity = reset($fbPageEntity);
    $pageId = $fbPageEntity->get('field_page_id')->value;
    
    return $pageId;
  }

  /**
   * Get the client's stored FB page access token.
   */
  public function getClientPageAccessToken($client) {
    $user_storage = $this->tools->getStorage('user');
    $client = $user_storage->load($client);
    $fbPageEntity = $client->field_fb_page->referencedEntities();

    if (empty($fbPageEntity)) {
      return;
    }

    $fbPageEntity = reset($fbPageEntity);
    $pageAccessToken = $fbPageEntity->get('field_page_access_token')->value;

    return $pageAccessToken;
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
