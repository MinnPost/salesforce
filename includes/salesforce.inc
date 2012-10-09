<?php
/**
 * @file
 * Objects, properties, and methods to communicate with the Salesforce REST API
 */

/**
 *
 */
class Salesforce {

  public $consumer_key;
  public $consumer_secret;

  public $login_url = 'https://login.salesforce.com';

  //@todo: does this need to be configurable?
  public $rest_api_version = array(
    "label" => "Summer '12",
    "url" => "/services/data/v25.0/",
    "version" => "25.0"
  );

  public function __construct($consumer_key, $consumer_secret = '') {
    $this->consumer_key = $consumer_key;
    $this->consumer_secret = $consumer_secret;
  }

  public function apiCall($path, $params = array(), $method = 'GET') {
    if (!$this->getAccessToken()) {
      $this->refreshToken();
    }

    $url = $this->getInstanceURL() . $this->rest_api_version['url'] . $path;

    $options = array(
      'method' => $method,
      'headers' => array(
        'Authorization: OAuth' => $this->getAccessToken()
      ),
      'data' => drupal_http_build_query($params)
    );

    $response = drupal_http_request($url, $options);
    $data = drupal_json_decode($response->data);

    if (isset($data['error'])) {
      throw new SalesforceException($data['error_description'], $data['error']);
    }

    return $data;
  }

  private function getInstanceURL() {
    return variable_get('salesforce_instance_url', '');
  }

  private function setInstanceURL($url) {
    variable_set('salesforce_instance_url', $url);
  }

  private function refreshToken() {
    $token_url = $this->login_url . '/services/oauth2/token';

    $query = array(
      'grant_type' => 'refresh_token',
      'refresh_token' => $this->getRefreshToken(),
      'client_id' => $this->consumer_key,
      'client_secret' => $this->consumer_secret,
    );

    $response = drupal_http_request($token_url, array(
      'data' => drupal_http_build_query($query),
      'method' => 'POST',
      'headers' => array(
        // this is an undocumented requirement on SF's end
        'Content-Type' => 'application/x-www-form-urlencoded'
      )
    ));

    if ($response->code != 200) {
      //@todo: deal with error better
      throw new SalesforceException(t('Unable to get a Salesforce access token.'), $response->code);
    }

    $data = drupal_json_decode($response->data);

    if (isset($data['error'])) {
      throw new SalesforceException($data['error_description'], $data['error']);
    }

    $this->setInstanceURL($data['instance_url']);
    $this->setAccessToken($data['access_token']);
  }

  private function getRefreshToken() {
    return variable_get('salesforce_refresh_token', '');
  }

  private function setRefreshToken($token) {
    variable_set('salesforce_refresh_token', $token);
  }

  public function getAuthorizationCode() {
    $url = $this->login_url . '/services/oauth2/authorize';
    $query = array(
      'redirect_uri' => $this->redirectUrl(),
      'response_type' => 'code',
      'client_id' => $this->consumer_key,
    );

    drupal_goto($url, array('query' => $query));
  }

  public function requestToken($code) {
    $token_url = $this->login_url . '/services/oauth2/token';

    $query = array(
      'code' => $code,
      'grant_type' => 'authorization_code',
      'client_id' => $this->consumer_key,
      'client_secret' => $this->consumer_secret,
      'redirect_uri' => $this->redirectUrl()
    );

    $response = drupal_http_request($token_url, array(
      'data' => drupal_http_build_query($query),
      'method' => 'POST',
      'headers' => array(
        // this is an undocumented requirement on SF's end
        'Content-Type' => 'application/x-www-form-urlencoded'
      )
    ));

    if ($response->code != 200) {
      //@todo: deal with error
    }

    $data = drupal_json_decode($response->data);

    $this->setRefreshToken($data['refresh_token']);
    $this->setInstanceURL($data['instance_url']);
    $this->setAccessToken($data['access_token']);
  }

  private function setAccessToken($token) {
    $_SESSION['salesforce_access_token'] = $token;
  }

  private function getAccessToken() {
    return isset($_SESSION['salesforce_access_token']) ? $_SESSION['salesforce_access_token'] : FALSE;
  }

  private function redirectUrl() {
    return url('salesforce/oauth_callback', array(
      'absolute' => TRUE,
      'https' => TRUE
    ));
  }

  // wrapper calls around core apiCall() follow.

  /**
   * Available objects and their metadata for your organization's data.
   */
  public function objects() {
    return $this->apiCall('sobjects');
  }

}

class SalesforceException extends Exception {}