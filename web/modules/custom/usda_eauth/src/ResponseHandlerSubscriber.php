<?php

namespace Drupal\usda_eauth;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response handler class for checking zroles credentials.
 */
class ResponseHandlerSubscriber implements EventSubscriberInterface {
  // Define rate at which to check auth expiration refresh.
  const REFRESH_RATE = 60 * 30;

  /**
   * Get all requests to be handled.
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'responseHandler',
    ];
  }

  /**
   * Check if eauth user is valid.
   */
  public function checkZroles($eAuthId, $session) {
    $zroles_util = \Drupal::service('usda_eauth.zroles');
    if ($eAuthId == NULL) {
      return TRUE;
    }
    $ret = $zroles_util->getUserAccessRolesAndScopes($eAuthId);

    $currRole = $zroles_util->getTokenValue($ret, 'ApplicationRoleEnumeration');
    $oldRole = $session->get('ApplicationRoleEnumeration');
    \Drupal::logger("roles_compare")->error('old role: ' . $oldRole . ' new role: ' . $currRole . ' eauthID ' . $eAuthId);

    return $currRole == $oldRole; 
  }

  /**
   * Logs out the user from drupal session.
   */
  public function logoutUser($session) {
    user_logout();
  }

  /**
   * Gives a new auth experation time only if valid user. If not, logs them out.
   */
  public function refreshAuthExpiryMark($session, $eAuthId) {
    if ($this->checkZroles($eAuthId, $session)) {
      $session->set('auth_expiry_mark', time());
      \Drupal::logger("auth_scan")->error("set new mark for " . $eAuthId);
    }
    else {
      \Drupal::logger("auth_scan")->error("kicked out " . $eAuthId);
      $this->logoutUser($session);
    }
  }

  /**
   * Handles all requests made in PODS.
   */
  public function responseHandler(ResponseEvent $event) {
    $session = \Drupal::request()->getSession();
    $eAuthId = $session->get("eAuthId");

    $auth_expiry = $session->get("auth_expiry_mark");
    
    // Sets a new eauth expiration time for a valid eauth user
    // if no expiration time has been set or time is up.
    if (($auth_expiry == NULL) || ((time() - $auth_expiry) > self::REFRESH_RATE)) {
      $this->refreshAuthExpiryMark($session, $eAuthId);
    }
  }

}
