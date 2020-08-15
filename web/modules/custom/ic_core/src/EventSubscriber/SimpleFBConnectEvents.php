<?php

namespace Drupal\ic_core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Drupal\ic_core\IcCoreTools;

/**
 * A class for capturing the events, dispatch by Simple FB Connect module.
 */
class SimpleFBConnectEvents implements EventSubscriberInterface {
  /**
   * ICA Specific Tools.
   *
   * @var \Drupal\ic_core\IcCoreTools
   */
  protected $tools;

  /**
   * Constructor.
   *
   * @param \Drupal\ic_core\IcCoreTools $ic_core_tools
   *   Utility class.
   */
  public function __construct(IcCoreTools $ic_core_tools) {
    $this->tools = $ic_core_tools;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // When a user is created through simple fb connect or,
    // when a user login to the site using simple fb connect,
    // we wanna subscribe to that event and do something.
    return([
      'simple_fb_connect.user_created' => [
        ['getFbConnectEvents'],
      ],
      'simple_fb_connect.user_login' => [
        ['getFbConnectEvents'],
      ],
    ]);
  }

  /**
   * Listener for the FB Connect events.
   *
   * @param \Symfony\Component\EventDispatcher\GenericEvent $event
   *   The event object dispatched by simple fb connect module.
   */
  public function getFbConnectEvents(GenericEvent $event) {
    // Upon checking, the token is a long lived token.
    // A 60 days expiration token.
    $accessToken = $this->tools->getSessionKey('simple_fb_connect_access_token');
    $this->tools->setFbData($accessToken, $event);
  }

}
