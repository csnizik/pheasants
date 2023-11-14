<?php

namespace Drupal\usda_eauth;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * User login block prerender logic.
 */
class UserLoginBlock implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Prerender to remove "Reset your password" link from user login block.
   */
  public static function preRender($build) {
    unset($build['content']['user_links']);
    return $build;
  }

}
