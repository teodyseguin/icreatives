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
    $roles = $this->tools->getCurrentRoles();
    $currentUser = $this->tools->getCurrentUser();
    $currentUserId = $currentUser->id();

    if (!in_array('client', $roles)) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ($request->query->has('field_client_target_id')) {
      return;
    }
    else if ($request->query->has('client')) {
      return;
    }

    switch ($path) {
      case '/dashboard':
        $request->query->set('client', $currentUserId);
        $path = "$path?client=$currentUserId";
        $this->tools->pageRedirect($path);
      break;

      case '/inbox':
      case '/invoice':
      case '/products':
        $request->query->set('field_client_target_id', $currentUserId);
        $path = "$path?field_client_target_id=$currentUserId";
        $this->tools->pageRedirect($path);
      break;
    }
  }

}
