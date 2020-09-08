<?php

namespace Drupal\ic_core;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Account Interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used for reading data from and writing data to session.
   */
  public function __construct(SessionInterface $session, CurrentPathStack $currentPath, AliasManager $aliasManager, PathMatcher $pathMatcher, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser) {
    $this->session = $session;
    $this->currentPath = $currentPath;
    $this->aliasManager = $aliasManager;
    $this->pathMatcher = $pathMatcher;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
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

  /**
   * Get the Entity storage.
   *
   * @param string $storgae
   *   The name of the storage.
   */
  public function getStorage($storage) {
    return $this->entityTypeManager->getStorage($storage);
  }

  /**
   * Get all the client users in the site.
   */
  public function getClients() {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $query = $userStorage->getQuery();
    $uids = $query->condition('status', '1')
                  ->condition('roles', 'client')
                  ->execute();

    return $userStorage->loadMultiple($uids);
  }

  /**
   * Check if the current user is an anonymous user.
   */
  public function isAnonymous() {
    return $this->currentUser->isAnonymous();
  }

  /**
   * Get the roles of the current logged in user.
   */
  public function getCurrentRoles() {
    return $this->currentUser->getRoles();
  }

  /**
   * Return the current user;
   */
  public function getCurrentUser() {
    return $this->currentUser;
  }

  /**
   * Get the site admin user.
   */
  public function getSiteAdmin() {
    $ids = $this->getStorage('user')->getQuery()
    ->condition('status', 1)
    ->condition('roles', 'site_admin')
    ->execute();

    return $this->getStorage('user')->loadMultiple($ids);
  }

  /**
   * Get the full name of the user field field first and last name is not empty.
   * Otherwise, this function will return the username of the current user.
   */
  public function getUserFullName($current_user_id = NULL) {
    $user_storage = $this->getStorage('user');

    if (!$current_user_id) {
      $current_user_id = $this->getCurrentUser()->id();
    }

    $current_user = $user_storage->load($current_user_id);

    $first_name = $current_user->field_first_name->getValue();
    $last_name = $current_user->field_last_name->getValue();

    if (!empty($first_name)) {
      $first_name = reset($first_name);
      $first_name = $first_name['value'];
    }

    if (!empty($last_name)) {
      $last_name = reset($last_name);
      $last_name = $last_name['value'];
    }

    $full_name = "$first_name $last_name";

    if ($full_name == '') {
      return $current_user->get('name')->value;
    }
    else {
      return $full_name;
    }
  }

  /**
   * Redirect to a page based on the given path.
   */
  public function pageRedirect($path) {
    $response = new RedirectResponse($path);
    $request = \Drupal::request();
    $request->getSession()->save();
    $response->prepare($request);
    \Drupal::service('kernel')->terminate($request, $response);
    $response->send();
  }

}
