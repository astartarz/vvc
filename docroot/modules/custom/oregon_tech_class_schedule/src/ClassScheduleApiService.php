<?php
/**
 * OIT Class Schedule
 *
 * @package     oregon_tech_class_schedule
 * @author      Kwall <info@kwallcompany.com>
 * @license     GPL-2.0+
 * @link        http://www.kwallcompany.com/
 * @copyright   KwallCompany
 * Date:        07/28/2020
 * Time:        05:36 PM
 */

namespace Drupal\oregon_tech_class_schedule;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class ClassScheduleApiService {

  /**
   * Api End Point.
   *
   * @var string
   */
  protected $apiEndpoint = 'https://app-otfacts.azurewebsites.net/api';

  /**
   * Curator logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Http client object.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Cache Object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * ClassScheduleApiService constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   * @param \GuzzleHttp\ClientInterface $httpClient
   */
  public function __construct(LoggerChannelFactoryInterface $channelFactory,
                              ClientInterface $httpClient,
                              CacheBackendInterface $cache) {
    $this->logger = $channelFactory->get('curator');
    $this->httpClient = $httpClient;
    $this->cache = $cache;
  }

  public function getTerms() {
    $apiData = [];

    $_term_cache = $this->cache->get('csas_terms');

    if (!$_term_cache) {
      $url = $this->apiEndpoint . '/terms';
      $terms = $this->_get($url);

      if (!empty($terms)) {
        foreach ($terms as $term) {
          $apiData['options'][$term->id] = $term->description;
          if ($term->default) {
            $apiData['selected'] = $term->id;
          }
        }
        $this->cache->set('csas_terms', $apiData);
      }
    }
    else {
      $apiData = $_term_cache->data;
    }

    return $apiData;
  }

  public function getSubjects() {
    $apiData = [
      '' => 'All Non-Online Courses',
    ];

    $_subject_cache = $this->cache->get('csas_subjects');

    if (!$_subject_cache) {
      $url = $this->apiEndpoint . '/courses/subjects';

      $subjects = $this->_get($url);

      if (!empty($subjects)) {
        foreach ($subjects as $subject) {
          $apiData[$subject->prefix] = $subject->name;
        }
        $this->cache->set('csas_subjects', $apiData);
      }
    }
    else {
      $apiData = $_subject_cache->data;
    }

    return $apiData;
  }

  private function _get($url) {

    if ($url) {
      try {

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

      } catch (\Exception $e) {
        // Exception.
        $this->logger->notice($e->getMessage());
      }
    }
    return [];
  }

  public function getLevels() {
    return [
      '' => "All",
      '100|199' => '100-199',
      '200|299' => '200-299',
      '300|399' => '300-399',
      '400|499' => '400-499',
      '500|999' => '>499',
    ];
  }

  public function getCampus() {
    return [
      'Chemeketa' => 'Chemeketa',
      'Klamath' => 'Klamath Falls',
      'Online' => 'Online',
      'Portland-Metro' => 'Portland-Metro',
      'Seattle' => 'Seattle',
      '0' => '-All Campuses-',
    ];
  }

  public function getCostOfMaterials() {
    return [
      'A' => 'Show All',
      '0' => 'No Cost',
      '50' => 'Low or No Cost ($50 or less)',
    ];
  }

  public function getDay() {
    return [
      'M' => 'Mon',
      'T' => 'Tue',
      'W' => 'Wed',
      'R' => 'Thu',
      'F' => 'Fri',
    ];
  }

  public function getTime() {
    return [
      '' => 'All',
      '0700' => '7AM',
      '0800' => '8AM',
      '0900' => '9AM',
      '1000' => '10AM',
      '1100' => '11AM',
      '1200' => '12PM',
      '1300' => '1PM',
      '1400' => '2PM',
      '1500' => '3PM',
      '1600' => '4PM',
      '1700' => '5PM',
      '1800' => '6PM',
      '1900' => '7PM',
      '2000' => '8PM',
      '2100' => '9PM',
    ];
  }

}
