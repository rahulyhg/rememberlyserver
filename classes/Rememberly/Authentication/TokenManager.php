<?php

namespace Rememberly\Authentication;

use \Firebase\JWT\JWT;

class TokenManager
{
    private $settings;
    public function __construct($settings)
    {
        $this->settings = $settings;
    }
    public function createUserToken($userID, $username, $todolistPermissions, $notePermissions, $androidAppID)
    {
        $iat = time();
        $exp = time() + 7200; // Token expires after 2 Hours
  // TODO: Add REG_ID from Android
  $settingsArray = $this->settings->get('settings'); // get settings array.
  $token = JWT::encode(
      ['userID' => $userID, 'username' => $username, 'todolistPermissions' => $todolistPermissions,
  'notePermissions' => $notePermissions, 'androidAppID' => $androidAppID, 'iat' => $iat, 'exp' => $exp],
   $this->settings['jwt']['secret'],
      "HS256"
  );
        return $token;
    }
}
