<?php

namespace Drupal\httpclientservice\Client;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\guzzle_cache\DrupalGuzzleCache;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;

/**
 * Implementation of an Asiakaspalvelijoiden palvelukortit API connection client.
 *
 * @package Drupal\httpclientservice\Client
 */
class httpclientserviceClient implements httpclientserviceClientInterface {

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A configuration instance.
   *
   * @var \Drupal\Core\Config\ConfigInterface
   */
  protected $config;

  /**
   * API Caller.
   *
   * @var string
   */
  protected $caller;

  /**
   * API Public Key.
   *
   * @var string
   */
  protected $publicKey;

  /**
   * API Base URL.
   *
   * @var string
   */
  protected $baseURI;

  /**
   * Call string for Asiakaspalvelijoiden palvelukortit API.
   *
   * Consists endpoint and possible query string.
   *
   * @var string
   */
  protected $callString;

  /**
   * Constructor.
   */
  public function __construct(ClientInterface $http_client, KeyRepositoryInterface $key_repo, ConfigFactory $config_factory) {
    $this->httpClient = $http_client;
    $config = $config_factory->get('httpclientservice.settings');
    $this->caller = $config->get('httpclientservice_caller');
    $this->caller = $key_repo->getKey($this->caller)->getKeyValue();
    $this->publicKey = $config->get('httpclientservice_public_key');
    $this->publicKey = $key_repo->getKey($this->publicKey)->getKeyValue();
    $this->baseURI = $config->get('httpclientservice_base_uri');
  }

  /**
   * Get cached client.
   */
  public function getCachedClient() {
    // Create default HandlerStack.
    $stack = HandlerStack::create();

    // Create a Drupal Guzzle cache.
    $cache = new DrupalGuzzleCache();

    // Push the cache to the stack.
    $stack->push(
      new CacheMiddleware(
        new PrivateCacheStrategy($cache)
      ),
      'cache'
    );

    // Initialize the client with the handler option.
    return new Client(['handler' => $stack]);
  }

  /**
   * {@inheritdoc}
   */
  public function connect($method, $endpoint, array $query) {
    // Build call string for the API.
    $this->buildCallString($endpoint, $query);

    // Connect to the client.
    // Connect to the client.
    try {
      $response = $this->getCachedClient()->{$method}(
        $this->baseURI . $this->callString,
        [
          'headers' => $this->getHttpHeaders(),
        ]
      );
    }
    catch (RequestException $exception) {
      drupal_set_message(t('Failed to complete Asiakaspalvelijoiden palvelukortit connection "%error"', ['%error' => $exception->getMessage()]), 'error');

      \Drupal::logger('httpclientservice')
        ->error('Failed to complete Asiakaspalvelijoiden palvelukortit connection "%error"', ['%error' => $exception->getMessage()]);
      return FALSE;
    }

    return $response->getBody()->getContents();
  }

  /**
   * Build options for the client.
   *
   * @param string $endpoint
   *   Endpoint as a string.
   * @param array $query
   *   Query parameters as array.
   */
  private function buildCallString($endpoint, array $query) {
    $this->callString = $endpoint;

    if (!empty($query)) {
      $this->callString .= '?' . UrlHelper::buildQuery($query);
    }
  }

  /**
   * Get HTTP Headers for the connection.
   *
   * @return array
   *   Return Headers.
   */
  public function getHttpHeaders() {
    $timestamp = time();
    $signature = hash_hmac(
      'sha256',
      base64_encode($this->caller . ',' . $timestamp . ',' . $this->callString . ',' . $this->publicKey),
      'turku_api'
    );

    return [
      'X-TURKU-SP' => $this->caller,
      'X-TURKU-TS' => $timestamp,
      'Authorization' => $signature,
    ];
  }

}
