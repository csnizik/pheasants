<?php

namespace Drupal\usda_eauth;

/**
 * Utilities service interface for zRoles.
 */
interface ZRolesUtilitiesInterface {

  /**
   * Get roles and scopes for an eAuthID.
   *
   * @param string $eAuthId
   *   The eAuth ID to load roles and scopes for.
   *
   * @return mixed
   *   List of roles.
   */
  public static function getUserAccessRolesAndScopes(string $eAuthId);

  /**
   * Get a list of employees with the specified zRole.
   *
   * @param string $zRole
   *   The zRole to search for.
   *
   * @return mixed
   *   List of employees.
   */
  public static function getListByzRole(string $zRole);

  /**
   * Use strpos and substr to get the value of a token from an XML string.
   *
   * Used to get around problems with non-breaking spaces.
   *
   * @param string $xmlString
   *   The XML string.
   * @param string $token
   *   The token string.
   *
   * @return string
   *   Returns the value of the token.
   */
  public static function getTokenValue($xmlString, $token);

  /**
   * Debug Only - function to print out the user information using print_r.
   *
   * @param object|array $user
   *   User object.
   */
  public static function printUserInfo($user);

}
