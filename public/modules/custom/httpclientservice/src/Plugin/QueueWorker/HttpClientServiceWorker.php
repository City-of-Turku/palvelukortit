<?php
/**
 * @file
 * Contains \Drupal\example_queue\Plugin\QueueWorker\ExampleQueueWorker.
 */

namespace Drupal\httpclientservice\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes tasks for example module.
 *
 * @QueueWorker(
 *   id = "httpclientservice_queue",
 *   title = @Translation("Http Client Service Worker"),
 *   cron = {"time" = 90}
 * )
 */
class HttpClientServiceWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

  }

}
