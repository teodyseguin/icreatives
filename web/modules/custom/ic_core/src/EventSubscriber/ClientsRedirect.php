<?php

namespace Drupal\ic_core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\ic_core\IcCoreTools;

/**
 * A class for capturing the events, dispatch by Simple FB Connect module.
 */
class ClientsRedirect implements EventSubscriberInterface {
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
    return([
      KernelEvents::REQUEST => [
        ['redirectToProperPage'],
      ]
    ]);
  }

  /**
   * Redirect the user to the front page or the login page if he is an anonymous user.
   */
  public function redirectToProperPage(GetResponseEvent $event) {
    // This is necessary because this also gets called on
    // node sub-tabs such as "edit", "revisions", etc.
    // This prevents those pages from redirected.
    // if ($this->request->attributes->get('_route') !== 'entity.node.canonical') {
    //   return;
    // }

    $roles = $this->tools->getCurrentRoles();
    $currentUser = $this->tools->getCurrentUser();
    $currentUserId = $currentUser->id();

    // Products page client
    // products?field_client_target_id=<user id>

    // Inbox per client
    // inbox?field_client_target_id=<user id>

    // Invoice per client
    // invoice?field_client_target_id=<user id>

    if (!in_array('client', $roles)) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ($request->query->has('field_client_target_id')) {
      return;
    }

    $request->query->set('field_client_target_id', $currentUserId);

    switch ($path) {
      case '/inbox':
      case '/invoice':
      case '/products':
        $path = "$path?field_client_target_id=$currentUserId";
        $this->tools->pageRedirect($path);
      break;
    }
  }

}
