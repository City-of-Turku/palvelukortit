<?php

namespace Drupal\httpclientservice\Client;

/**
 * Provides the interface for a connection to Asiakaspalvelijoiden palvelukortit API.
 */
interface HttpClientServiceClientInterface {

  /**
   * Utilizes Drupal's httpClient to connect to Lyyti API.
   *
   * @param string $method
   *   get, post, patch, delete, etc. See Guzzle documentation.
   * @param string $endpoint
   *   The API endpoint (ex. events)
   * @param array $query
   *   Query string parameters the endpoint allows.
   *
   * @return object
   *   \GuzzleHttp\Psr7\Response body
   */
  public function connect($method, $endpoint, array $query);

}
