<?php

namespace Drupal\custom_module\Feeds\Fetcher;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an OIT person fetcher.
 *
 * @FeedsFetcher(
 *   id = "oit_person",
 *   title = @Translation("OIT Person"),
 * )
 */
class OitPerson extends PluginBase implements ClearableInterface, FetcherInterface, ContainerFactoryPluginInterface {

  /**
   * Drupal file system helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The Drupal file system helper.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $sink = $this->fileSystem->tempnam('temporary://', 'feeds_http_fetcher');
    $sink = $this->fileSystem->realpath($sink);

    // get token;
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, 'https://apiauth.oit.edu/connect/token');
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Basic c2VydmljZS13d3c6MTJLXnN1Ml9qX1RpV1MyMTJiN2pwZWUz'));
    $data = array('grant_type' => 'client_credentials', 'scope=afacts-api afacts-api.service', 'client_id' => 'www-service', 'client_secret' => '12K^su2_j_TiWS212b7jpee3');
    curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
    $result = curl_exec($c);
    curl_close($c);
    $result = json_decode($result);

    if (isset($result->access_token) and strlen($result->access_token) > 5) {
      $authorization = "Authorization: Bearer " . $result->access_token;
      $url = 'https://afacts.oit.edu/api/directory/list';
      $c = curl_init();
      curl_setopt($c, CURLOPT_URL, $url);
      curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
      curl_setopt($c, CURLOPT_HTTPGET, true);
      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($c);
      curl_close($c);

      if (!empty($result)) {
        file_put_contents($sink, $result);
        return new FetcherResult($sink);
      }
      else {
        $state->setMessage($this->t('The request return empty result'));
      }
    }
    else {
      $state->setMessage($this->t('The source token is incorrect.'));
    }

    throw new EmptyFeedException();
  }


  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed, StateInterface $state) {

  }


}
