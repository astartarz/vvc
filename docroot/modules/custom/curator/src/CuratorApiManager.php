<?php
/**
 * Curator - Social post sync
 *
 * @package     curator
 * @author      Kwall <info@kwallcompany.com>
 * @license     GPL-2.0+
 * @link        http://www.kwallcompany.com/
 * @copyright   KwallCompany
 * Date:        06/26/2020
 * Time:        11:40 PM
 */

namespace Drupal\curator;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;

class CuratorApiManager {

  /**
   * Api post status.
   *
   * @var string
   */
  protected $status = 'active';

  /**
   * * Api key.
   *
   * @var $api_key
   */
  protected $api_key;

  /**
   * Api End Point.
   *
   * @var string
   */
  protected $apiEndpoint = 'https://api.curator.io';

  /**
   * Api Version.
   *
   * @var string
   */
  protected $apiVersion = 'v1';

  /**
   * Api slug.
   * Content importing slug.
   *
   * @var string
   */
  protected $apiSlug = 'feeds';

  /**
   * Curator logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * CuratorApiManager constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   */
  public function __construct(LoggerChannelFactoryInterface $channelFactory) {
    $this->logger = $channelFactory->get('curator');
    $this->api_key = \Drupal::config('curator.settings')->get('api_key');

    $this->apiUrl = $this->apiEndpoint . '/' . $this->apiVersion . '/' . $this->apiSlug;
  }


  public function getFeeds() {
    if ($this->api_key) {
      // Build query for API.
      $query = UrlHelper::buildQuery([
        'api_key' => $this->api_key,
      ]);

      try {
        $url = $this->apiUrl . '?' . $query;

        // Call api.
        $response = \Drupal::httpClient()->get(
          $url,
          [
            'headers' => [
              'Content-Type' => 'application/json',
              'Accept' => 'application/json',
            ],
          ]);

        // If code is 200.
        if ($response->getStatusCode() == 200) {
          // Store data.
          $data = (string) $response->getBody();

          if (!empty($data)) {
            // Convert data to json.
            $jsonData = \GuzzleHttp\json_decode($data);

            // Return a json object.
            return $jsonData;
          }
        }
        else {
          // If some other status code.
          $this->logger->notice($response->getStatusCode() . ':- Api call is not successful.');
        }
      } catch (RequestException $e) {
        // Exception.
        $this->logger->notice($e->getMessage());
      }
    }
    return [];
  }

  /**
   * Get social posts from api.
   * Return them back to use with batch process.
   *
   * @param int $limit
   * @param string $network
   *
   * @return bool|mixed
   */
  public function getPosts($feed_id, $limit = 20, $offset = 0, $network_id = 0) {

    // Run code if api key exist.
    if ($this->api_key && $feed_id) {

      // Parameter array.
      $params = [
        'api_key' => $this->api_key,
        'limit' => $limit,
        //        'status' => 1,
      ];

      // Offset
      if ($offset > 0) {
        $params['offset'] = $offset;
      }

      // Network id
      if ($network_id > 0) {
        $params['network_id'] = $network_id;
      }

      // Build query for API.
      $query = UrlHelper::buildQuery($params);

      try {
        $url = $this->apiUrl . '/' . $feed_id . '/posts' . '?' . $query;

        // Call api.
        $response = \Drupal::httpClient()->get(
          $url,
          [
            'headers' => [
              'Content-Type' => 'application/json',
              'Accept' => 'application/json',
            ],
          ]);

        // If code is 200.
        if ($response->getStatusCode() == 200) {
          // Store data.
          $data = (string) $response->getBody();

          // Convert data to json.
          $jsonData = \GuzzleHttp\json_decode($data);

          // If empty return false.
          if (empty($data) || $jsonData->postCount == 0) {
            $this->logger->notice('Empty response from api.');
            return FALSE;
          }

          // Return a json object.
          return $jsonData->posts;
        }
        else {
          // If some other status code.
          $this->logger->notice($response->getStatusCode() . ':- Api call is not successful.');
          return FALSE;
        }
      } catch (RequestException $e) {
        // Exception.
        $this->logger->notice($e->getMessage());
        return FALSE;
      }
    }
    else {
      $this->logger->notice('You must provide the Api key.');
      return FALSE;
    }
  }

}
