<?php

namespace Drupal\ic_core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    if (!in_array('client', $roles)) {
      return;
    }

    $request = $event->getRequest();

    dump($request);
  }

}
