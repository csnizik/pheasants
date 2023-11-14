<?php

namespace Drupal\usda_eauth\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * USDA eAuth config override class.
 */
class UsdaEAuthConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a new FarmierConfigOverride instance.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('settings'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Override the Generic OpenID Connect client to use credentials and
    // endpoints defined in settings.php.
    if (in_array('openid_connect.client.generic', $names)) {
      $overrides['openid_connect.client.generic']['settings']['client_id'] = $this->settings->get('client_id', '');
      $overrides['openid_connect.client.generic']['settings']['client_secret'] = $this->settings->get('client_secret', '');
      $eauth_url = $this->settings->get('eAuthBaseUrl', '');
      $overrides['openid_connect.client.generic']['settings']['authorization_endpoint'] = $eauth_url . '/authorize';
      $overrides['openid_connect.client.generic']['settings']['token_endpoint'] = $eauth_url . '/token';
      $overrides['openid_connect.client.generic']['settings']['userinfo_endpoint'] = $eauth_url . '/userinfo';
      $overrides['openid_connect.client.generic']['settings']['end_session_endpoint'] = $eauth_url . '/revoke';
      $overrides['openid_connect.client.generic']['settings']['scopes'] = ['openid', 'email', 'profile', 'usda_credential_data'];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'UsdaEAuthOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
