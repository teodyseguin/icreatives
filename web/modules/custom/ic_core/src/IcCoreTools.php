<?php

namespace Drupal\ic_core;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\PathMatcher;

/**
 * A utility class for various usage and purpose.
 */
class IcCoreTools {
  /**
   * Session Interface.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Current Path Stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Alias Manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Path Matcher.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used for reading data from and writing data to session.
   */
  public function __construct(SessionInterface $session, CurrentPathStack $currentPath, AliasManager $aliasManager, PathMatcher $pathMatcher) {
    $this->session = $session;
    $this->currentPath = $currentPath;
    $this->aliasManager = $aliasManager;
    $this->pathMatcher = $pathMatcher;
  }

  /**
   * Get the value of the session key property.
   *
   * @param string $key
   *   The name of the key property you want to access from the session object.
   */
  public function getSessionKey($key) {
    return $this->session->get($key);
  }

  /**
   * Do some stuff here using the retrieve FB access token.
   *
   * @param string $accessToken
   *   The access token returned by facebook.
   * @param \Symfony\Component\EventDispatcher\GenericEvent $event
   *   The event dispatched by simple facebook connect.
   */
  public function setFbData($accessToken, GenericEvent $event) {
    // The subject is actually the user object.
    $subject = $event->getSubject();
    // Update the field with the obtained access token.
    $subject->set('field_facebook_access_token', $accessToken);
    $subject->save();
  }

  /**
   * Get the current path.
   */
  public function getPath() {
    return $this->currentPath->getPath();
  }

  /**
   * Get the alias by the given path.
   *
   * @param string $path
   *   A given path from the site.
   */
  public function getAliasByPath($path) {
    return $this->aliasManager->getAliasByPath($path);
  }

  /**
   * Check if the current page is the front page of the site.
   */
  public function isFrontPage() {
    return $this->pathMatcher->isFrontPage();
  }

}
