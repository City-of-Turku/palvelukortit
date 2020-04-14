<?php

namespace Drupal\httpclientservice\Client;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactory;
use Drupal\guzzle_cache\DrupalGuzzleCache;
use Drupal\key\KeyRepositoryInterface;
use Drupal\node\Entity\Node;
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
class HttpClientServiceClient implements HttpClientServiceClientInterface {

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
    // 'YYYY-MM-DDThh:mm:ssZ' F.e. 2019-05-27T12:17:06.457Z;
    $timestamp = date('Y-m-d\TH:i:s.v\Z');
    $payload = '';
    // @TODO Move to private file and fetch with keys.
    $apikey = 'b80d2c033069be8c3bbd114ec2bac0dc45a5a0e19c8cbe06a00bd637d562500d';
    $signature = hash(
      'sha256',
      $this->caller . $timestamp . $payload . $apikey
    );

    return [
      'X-TURKU-SP' => $this->caller,
      'X-TURKU-TS' => $timestamp,
      'Authorization' => $signature,
    ];
  }

  /**
   * Create a new entity
   *
   * @param $data
   * @param $type
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createEntity($data, $type) {
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $type,
      'langcode' => 'fi',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => 1,
      'title' => 'My test!',
      //If you have another field lets says field_day you can do this:
      //'field_day' => 'value',
      'body' => [
        'summary' => '',
        'value' => '<p>The body of my node.</p>',
        'format' => 'full_html',
      ],
    ]);

    //Saving original the node
    $node->save();

    foreach ($this->translation as $tr) {
      $node_tr = $node->addTranslation($tr);
      $node_tr->title = 'Eng title';
      $node_tr->body = [
        'summary' => '',
        'value' => '<p>Eng body.</p>',
        'format' => 'full_html',
      ];
      //Saving translation
      $node_tr->save();
    }
  }

  /**
   * @param $nid
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateEntityByNid($nid) {
    if (!$node = Node::load($nid)) {
      return;
    }

    //save to update node
    $node->save();

    foreach ($this->translation as $tr) {
      if ($node->hasTranslation($tr)) {
        $tr_node = $node->getTranslation($tr);
        $tr_node->save();
      }
    }
  }

  public function getEntity() {
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('title', 'cat', 'CONTAINS')
      ->condition('field_tags.entity.name', 'cats');

    $nids = $query->execute();
  }

  public function deleteEntity($nid) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

    if ($node) {
      $node->delete();
    }
  }

  public function deleteMultipleEntitys($nids) {
    foreach($nids as $nid) {
      $this->deleteEntity($nid);
    }
  }

}
