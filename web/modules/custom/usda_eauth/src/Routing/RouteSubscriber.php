<?php

namespace Drupal\usda_eauth\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Deny access to user login, register, and password reset paths.
    $disallow_routes = [
      'user.login',
      'user.register',
      'user.pass',
    ];
    foreach ($disallow_routes as $disallow_route) {
      if ($route = $collection->get($disallow_route)) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
