<?php

namespace Drupal\ic_core;

use Drupal\ic_core\IcCoreTools;
use Drupal\Core\Database\Connection;
use Facebook\Exceptions\FacebookResponseException;

/**
 * A utility class for various usage and purpose.
 */
class IcIgService {

  /**
   * @var \Drupal\ic_core\IcCoreTools
   */
  protected $tools;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnection;

  /**
   * This will be the same FB service being used in FB Simple Connect module.
   */
  protected $fbService;

  /**
   * The number of followers.
   */
  protected $followersCount;

  /**
   * Constructor.
   */
  public function __construct(IcCoreTools $tools, Connection $dbConnection) {
    $this->tools = $tools;
    $this->dbConnection = $dbConnection;
    $this->fbService = $this->tools->getFbService();

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
   * Get the Instagram Followers count.
   */
  public function getIgFollowers() {
    $client = \Drupal::request()->query->get('client');

    if (!$client) {
      return;
    }

    $user_storage = $this->tools->getStorage('user');
    $client = $user_storage->load($client);
    $fbPageEntity = $client->field_facebook_page->referencedEntities();

    if (empty($fbPageEntity)) {
      return;
    }

    $fbPageEntity = reset($fbPageEntity);
    $pageId = $fbPageEntity->get('field_page_id')->value;
    $pageAccessToken = $fbPageEntity->get('field_page_access_token')->value;

    try {
      $response = $this->fbService->get("/$pageId/instagram_accounts?fields=id,followed_by_count&access_token=$pageAccessToken");

      if ($response) {
        $data = json_decode($response->getBody());

        if ($data) {
          $this->followersCount = $data->data[0]->followed_by_count;

          return ['total_instagram_followers' => $data->data[0]->followed_by_count];
        }
      }
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.ig_service')
        ->error('Something went wrong retrieving the instagram followers count : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Get the Instagram conversation count.
   */
  public function getIgConversations($from = NULL, $to = NULL) {
    $client = \Drupal::request()->query->get('client');
    $until = $to != NULL ? date('Y-m-d', $to) : date('Y-m-d', strtotime('now'));
    $since = $from != NULL ? date('Y-m-d', $from) : date('Y-m-d', strtotime('-30 days'));
    $igEntities = [];

    if (!$client) {
      return;
    }

    $igStorage = $this->tools->getStorage('ic_instagram');

    $queryString = "SELECT * FROM ic_instagram__field_date " .
                   "WHERE field_date_value <= '$until' AND field_date_value >= '$since'";
    $results = $this->dbConnection->query($queryString);

    foreach ($results as $key => $result) {
      $igEntities[] = $result->entity_id;
    }

    $data = $igStorage->loadMultiple($igEntities);

    return $this->createKeywordsCount($data);
  }

  /**
   * Get the list of facebook pages, owned by a user.
   */
  public function getIgPages() {
    $fbId = $this->fbId;
    $fbAccessToken = $this->fbAccessToken;

    if (!$fbId && !$fbAccessToken) {
      $this->tools->loggerFactory()
        ->get('ic_core.ig_service')
        ->notice('Facebook ID and Access token is not available.');

      return;
    }

    try {
      $response = $this->fbService->get("/$fbId/accounts?access_token=$fbAccessToken&fields=instagram_business_account{id,name}");

      if ($response) {
        $pages = json_decode($response->getBody());
        $this->upsertIgPages($pages);

        return $pages;
      }
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.ig_service')
        ->error('Something went wrong retrieving the instagram pages : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Create/Update IG Page Entities.
   *
   * @var $pages
   *   An array of Instagram pages with id.
   */
  public function upsertIgPages($pages) {
    if (!$pages && !$pages->data && count($pages->data) == 0) {
      return;
    }

    // Fetch all FB Page Entities.
    $instagramStorage = $this->tools->getStorage('ic_instagram');

    foreach ($pages->data as $data) {
      $id = $data->instagram_business_account->id;

      if (empty($id)) {
        continue;
      }

      $igPage = $instagramStorage->loadByProperties([
        'field_ig_page_id' => $id
      ]);

      if (empty($igPage)) {
        try {
          $igPage = $instagramStorage->create([
            'type' => 'instagram_page',
            'name' => $data->instagram_business_account->name,
            'field_ig_page_id' => $data->instagram_business_account->id,
            'field_page_id' => $data->id,
          ]);

          $igPage->save();
        }
        catch (Exception $e) {
          $this->tools->loggerFactory()
            ->get('ic_core.ig_service')
            ->error('Something weng wrong while creating IG Page Entity : @message', ['@message' => json_encode($e)]);
        }
      }
    }
  }

  /**
   * Get the Instragram Insights.
   *
   * @var $from
   *   The date from in 10 digit timestamp format.
   * @var $to
   *   The date to in 10 digit timestamp format.
   */
  public function getIgPageInsights($from = NULL, $to = NULL) {
    $client = \Drupal::request()->query->get('client');
    $until = $to ? $to : strtotime('now');
    $since = $from ? $from : strtotime('-30 days');

    if (!$client) {
      return;
    }

    $instagramStorage = $this->tools->getStorage('ic_instagram');
    $user_storage = $this->tools->getStorage('user');
    $client = $user_storage->load($client);
    $fbPageEntity = $client->field_facebook_page->referencedEntities();

    if (empty($fbPageEntity)) {
      return;
    }

    $fbPageEntity = reset($fbPageEntity);
    $pageId = $fbPageEntity->get('field_page_id')->value;
    $fbAccessToken = $this->fbAccessToken;

    $igPage = $instagramStorage->loadByProperties([
      'field_page_id' => $pageId,
    ]);

    if (empty($igPage)) {
      return;
    }

    $igPage = reset($igPage);
    $igPageId = $igPage->get('field_ig_page_id')->value;

    $followers = $this->getIgFollowers();

    $metric1Insights = $this->getMetric1($igPageId, $fbAccessToken, $since, $until);
    $metric2Insights = $this->getMetric2($igPageId, $fbAccessToken, $since, $until);
    $metric3Insights = $this->getMetric3($igPageId, $fbAccessToken, $since, $until);
    
    $final = array_merge($metric1Insights, $metric2Insights, $metric3Insights, $followers);

    return $final;
  }

  /**
   * Get the Instagram metric which are the Reach and Impressions.
   */
  public function getMetric1($igPageId, $fbAccessToken, $since, $until) {
    // Reach and Impressions can be of period day.
    $metric = "reach,impressions,website_clicks,text_message_clicks,phone_call_clicks,get_directions_clicks";
    $insights = [
      'reach' => 0,
      'impressions' => 0
    ];

    try {
      $response = $this->fbService->get("/$igPageId/insights?access_token=$fbAccessToken&metric=$metric&period=day&since=$since&until=$until");
      $body = json_decode($response->getBody());

      if (count($body->data) == 0) {
        return;
      }

      foreach ($body->data as $data) {
        switch ($data->name) {
          case 'reach':
            $insights['reach'] = $this->getIgReach($data);
          break;

          case 'impressions':
            $insights['impressions'] = $this->getIgImpressions($data);
          break;

          case 'website_clicks':
          case 'text_message_clicks':
          case 'phone_call_clicks':
          case 'get_directions_click':
            $insights['link_clicks'] += $this->linkClicks($data);
          break;
        }
      }

      return $insights;
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
           ->get('ic_core.ig_service')
           ->error('Something weng wrong while retrieving IG Page data : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Get the Audience per city, country, gender and age.
   */
  public function getMetric2($igPageId, $fbAccessToken, $since, $until) {
    // Audience per city, country and gender age can only be of period lifetime.
    $metric = "audience_city,audience_country,audience_gender_age";
    $insights = [
      'location_followers' => 0,
      'gender_age_followers' => 0,
    ];

    try {
      // Things to note on this call:
      // - audience_city supports querying data only till yesterday
      $response = $this->fbService->get("/$igPageId/insights?access_token=$fbAccessToken&metric=$metric&period=lifetime");
      $body = json_decode($response->getBody());

      if (count($body->data) == 0) {
        return;
      }

      foreach ($body->data as $data) {
        switch ($data->name) {
          case 'audience_city':
            $insights['location_followers'] = $this->getIgAudienceCity($data);
          break;

          case 'audience_gender_age':
            $insights['gender_age_followers'] = $this->getIgAudienceGenderAge($data);
          break;
        }
      }

      return $insights;
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
           ->get('ic_core.ig_service')
           ->error('Something weng wrong while retrieving IG Page data : @message', ['@message' => json_encode($e)]);
    }
  }

  public function getMetric3($igPageId, $fbAccessToken, $since, $until) {
    $popular = [];

    try {
      $response = $this->fbService->get("/$igPageId?access_token=$fbAccessToken&fields=media{like_count,media_url,permalink,timestamp}&period=day&since=$since&until=$until");
      $body = json_decode($response->getBody());

      if (count($body->media->data) == 0) {
        return;
      }

      $mediaIds = [];

      foreach ($body->media->data as $data) {
        $mediaIds[] = $data->id;

        $popular[$data->like_count] = [
          'like_count' => $data->like_count,
          'picture' => $data->media_url,
          'link' => $data->permalink,
          'created' => date('m/d/Y', strtotime($data->timestamp)),
        ];
      }

      krsort($popular);

      return [
        'topFive' => array_splice($popular, 0, 5, TRUE),
        'engagementRate' => $this->getEngagementRate($mediaIds),
      ];
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
           ->get('ic_core.ig_service')
           ->error('Something weng wrong while retrieving IG Page data : @message', ['@message' => json_encode($e)]);
    }
  }

  public function getEngagementRate($mediaIds) {
    $fbAccessToken = $this->fbAccessToken;

    foreach ($mediaIds as $mediaId) {
      $path = "/$mediaId/insights?access_token=$fbAccessToken&metric=engagement&period=lifetime";

      try {
        $response = $this->fbService->get($path);
        $body = json_decode($response->getBody());
        $engagement = 0;

        if (count($body->data) == 0) {
          return;
        }

        foreach ($body->data as $data) {
          switch ($data->name) {
            case 'engagement':
              $engagement += $data->values[0]->value;
            break;
          }
        }

        return ($engagement/$this->followersCount) * 100;
      }
      catch (FacebookResponseException $e) {
        $this->tools->loggerFactory()
          ->get('ic_core.fb_service')
          ->error('Something went wrong while retrieving engagement rate : @message', [
            '@message' => json_encode($e),
          ]);
      }
    }
  }

  /**
   * Get the Instagram reach.
   */
  public function getIgReach($data) {
    $reach_count = [];

    foreach ($data->values as $value) {
      $reach_count[] = $value->value;
    }

    return array_sum($reach_count);
  }

  /**
   * Get the Instagram impressions.
   */
  public function getIgImpressions($data) {
    $impressions_count = [];

    foreach ($data->values as $value) {
      $impressions_count[] = $value->value;
    }

    return array_sum($impressions_count);
  }

  /**
   * Get the Total link clicks.
   */
  public function linkClicks($data) {
    $clicks_count = 0;

    foreach ($data->values as $value) {
      $clicks_count += $value->value;
    }

    return $clicks_count;
  }

  public function getIgAudienceCity($data) {
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

  public function getIgAudienceGenderAge($data) {
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
