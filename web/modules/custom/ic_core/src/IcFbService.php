<?php

namespace Drupal\ic_core;

use Drupal\ic_core\IcCoreTools;
use Facebook\Exceptions\FacebookResponseException;
use Drupal\Core\Database\Connection;

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
   * The database connection object.
   */
  protected $dbConnection;

  /**
   * Constructor.
   */
  public function __construct(IcCoreTools $tools, Connection $dbConnection) {
    $this->tools = $tools;
    $this->fbService = $tools->getFbService();
    $this->dbConnection = $dbConnection;

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
    $icFacebookStorage = $this->tools->getStorage('ic_facebook_entity');

    foreach ($pages->data as $data) {
      $fbPage2 = $icFacebookStorage->loadByProperties(['field_page_id' => $data->id]);

      if (empty($fbPage2)) {
        $facebookPageEntity = $icFacebookStorage->create([
          'type' => 'facebook_page',
          'name' => $data->name,
          'field_page_id' => $data->id,
          'field_page_access_token' => (string) $data->access_token,
        ]);

        $facebookPageEntity->save();
      }
      else {
        $fbPage2 = reset($fbPage2);
        $fbPage2->set('field_page_access_token', (string) $data->access_token);
        $fbPage2->save();
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
    $fbPageEntity = $client->field_facebook_page->referencedEntities();

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
    $fbPageEntity = $client->field_facebook_page->referencedEntities();

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
  public function getFbConversations($from = NULL, $to = NULL, $client = NULL, $after = NULL) {
    $userStorage = $this->tools->entityTypeManager()->getStorage('user');
    $client = $client ? $client : \Drupal::request()->query->get('client');
    $until = $to ? $to : strtotime('now');
    $since = $from ? $from : strtotime('-30 days');

    if (!$client) {
      return;
    }

    $pageId = $this->getClientPageId($client);

    if (!$pageId) {
      $this->tools->messenger()->addMessage(t("Page ID for client $client is missing. Please check client $client profile."), 'error');
      return;
    }

    $pageAccessToken = $this->getClientPageAccessToken($client);

    if (!$pageAccessToken) {
      $this->tools->messenger()->addMessage(t("Page Acces Token for client $client is missing. Please check client $client profile."), 'error');
      return;
    }

    $icFacebookStorage = $this->tools->getStorage('ic_facebook_entity');
    $fbMessageEntities = $this->tools->getStorage('ic_facebook_entity')->loadByProperties(['type' => 'facebook_message', 'field_client' => $client]);
    $userClient = $userStorage->load($client);
    $clientName = $userClient->getAccountName();
    $fbMessageList = [];

    foreach ($fbMessageEntities as $fbMessageEntity) {
      $messageId = $fbMessageEntity->get('field_message_id')->value;

      if (!empty($messageId)) {
        $fbMessageList[$messageId] = $fbMessageEntity;
      }
    }

    try {
      $path = "/$pageId/conversations?access_token=$pageAccessToken&fields=messages{message,created_time}&since=$since&until=$until";

      if ($after) {
        $path = "/$pageId/conversations?access_token=$pageAccessToken&fields=messages{message,created_time}&after=$after";
      }

      $response = $this->fbService->get($path);
      $body = json_decode($response->getBody());

      if (count($body->data) == 0) {
        return;
      }

      $this->fbConversationsProcessData($body->data, $icFacebookStorage, $fbMessageList, $clientName, $client, $pageAccessToken);

      if (isset($body->paging->cursors->after) && isset($body->paging->next)) {
        $queue = \Drupal::queue('fb_conversation_queue');
        $queue->createQueue();
        $queue->createItem($after);
      }
    }
    catch (FacebookResponseException $e) {
      $this->tools->loggerFactory()
        ->get('ic_core.fb_service')
        ->error('Something went wrong while retrieving the conversations : @message', ['@message' => json_encode($e)]);
    }
  }

  /**
   * Process the conversation data from Facebook conversations API.
   *
   * @param $datas
   *   An array of conversation objects.
   * @param $icFacebookStorage
   *   The ic_facebook_entity storage.
   * @param $fbMessageEntities
   *   An array of facebook_message entities.
   * @param $clientName
   *   The name of the client. The username. A string.
   * @param $client
   *   Represents the client id.
   * @param $pageAccessToken
   *   The facebook page access token.
   */
  public function fbConversationsProcessData($datas, $icFacebookStorage, &$fbMessageList, $clientName, $client, $pageAccessToken) {
    // Let's try to collect first all the messages.
    foreach ($datas as $data) {
      if (count($data->messages->data) == 0) {
        continue;
      }

      foreach ($data->messages->data as $message) {
        if (array_key_exists($message->id, $fbMessageList)) {
          continue;
        }

        $facebookMessageEntity = $icFacebookStorage->create([
          'type' => 'facebook_message',
          'name' => "[$clientName] " . $message->created_time . ' - message',
          'field_message_content' => $message->message,
          'field_created_time' => date('Y-m-d', strtotime($message->created_time)),
          'field_message_id' => $message->id,
          'field_client' => $client,
        ]);

        $facebookMessageEntity->save();
        // We make sure to update the list of fb messages to prevent duplicates.
        $fbMessageList[$message->id] = $facebookMessageEntity;
      }

      // We are doing cursor base pagination here.
      if (isset($data->messages->paging->cursors->after) && isset($data->messages->paging->next)) {
        $after = $data->messages->paging->cursors->after;

        if (property_exists($data->messages, 'id')) {
          $messagesId = $data->messages->id;
          $path = "/$messagesId/messages?access_token=$pageAccessToken&fields=messages{message,created_time}&after=$after";

          $queueData = [
            'path' => $path,
            'icFacebookStorage' => $icFacebookStorage,
            'fbMessageList' => $fbMessageList,
            'clientName' => $clientName,
            'client' => $client,
            'pageAccessToken' => $pageAccessToken,
          ];

          $queue = \Drupal::queue('fb_inner_conversation_queue');
          $queue->createQueue();
          $queue->createItem($queueData);
        }
      }
    }
  }

  /**
   * Create the keywords count.
   */
  public function createFbMessageKeywordsCount($client, $from, $to) {
    $icFacebookStorage = $this->tools->getStorage('ic_facebook_entity');

    if (empty($client) && empty($from) && empty($to)) {
      return;
    }

    $until = date('Y-m-d', $to);
    $since = date('Y-m-d', $from);
    $fbMessageEntities = [];
    $queryString = "SELECT icfefct.entity_id FROM ic_facebook_entity__field_created_time icfefct LEFT JOIN ic_facebook_entity__field_client icfefc ON icfefct.entity_id = icfefc.entity_id WHERE icfefct.field_created_time_value <= '$until' AND icfefct.field_created_time_value >= '$since' AND icfefc.field_client_target_id = $client";
    $results = $this->dbConnection->query($queryString);

    foreach ($results as $key => $result) {
      $fbMessageEntities[] = $result->entity_id;
    }

    $messages = $icFacebookStorage->loadMultiple($fbMessageEntities);
    $conversationTags = $this->getConversationTags();

    if (empty($conversationTags)) {
      return;
    }

    $checkChunksForTag = function($chunks, &$conversationTags) {
      $count = 0;

      foreach ($chunks as $chunk) {
        if (strpos($chunk, '#') !== FALSE && array_key_exists($chunk, $conversationTags)) {
          $conversationTags[$chunk]['total'] += 1;
          $count++;
        }
      }

      return $count > 0 ? TRUE : FALSE;
    };

    $tagsFound = 0;

    foreach ($messages as $message) {
      $content = $message->get('field_message_content')->value;
      $content = str_replace(' ', '-', trim(preg_replace('/\s\s+/', ' ', $content)));
      $chunks = explode('-', $content);
      $found = $checkChunksForTag($chunks, $conversationTags);

      if ($found) {
        $tagsFound++;
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
    $fbPageEntity = $client->field_facebook_page->referencedEntities();

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
    $fbPageEntity = $client->field_facebook_page->referencedEntities();

    if (empty($fbPageEntity)) {
      return;
    }

    $fbPageEntity = reset($fbPageEntity);
    $pageAccessToken = $fbPageEntity->get('field_page_access_token')->value;

    return $pageAccessToken;
  }

}
