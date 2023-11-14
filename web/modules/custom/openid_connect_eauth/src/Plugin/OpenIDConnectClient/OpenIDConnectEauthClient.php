<?php

namespace Drupal\openid_connect_eauth\Plugin\OpenIDConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;

/**
 * OpenID Connect eAuth client.
 *
 * Provides a login that utilizes USDA's eAuth.
 *
 * @OpenIDConnectClient(
 *  id = "eauth",
 *  label = @Translation("USDA eAuth")
 * )
 */
class OpenIDConnectEauthClient extends OpenIDConnectClientBase {

  /**
   * Overrides OpenIDConnectClientBase::getEndpoints().
   *
   * @return array
   *  Endpoint details with authorization endpoint, user access token
   *  and userinfo object.
   */
  public function getEndpoints() {
    $endpoints = [];
    $endpoints['authorization'] = $this->configuration['authorization_endpoint'];
    $endpoints['token'] = $this->configuration['token_endpoint'];
    $endpoints['userinfo'] = $this->configuration['userinfo_endpoint'];
    return $endpoints;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['authorization_endpoint'] = [
      '#title' => $this->t('Authorization endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['authorization_endpoint'],
    ];
    $form['token_endpoint'] = [
      '#title' => $this->t('Token endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['token_endpoint'],
    ];
    $form['userinfo_endpoint'] = [
      '#title' => $this->t('UserInfo endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['userinfo_endpoint'],
    ];
    $form['add_additional_fields'] = [
      '#title' => $this->t('TODO: Add additional configurations as needed.'),
      '#type' => 'item',
    ];

    return $form;
  }
}


