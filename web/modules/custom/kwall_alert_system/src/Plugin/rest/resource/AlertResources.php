<?php
/**
 * Kwall Alert System
 *
 * @package     kwall_alert_system
 * @author      Kwall <info@kwallcompany.com>
 * @license     GPL-2.0+
 * @link        https://kwallcompany.com/
 * @copyright   KwallCompany
 * Date:        07/29/2020
 * Time:        10:16 AM
 */

namespace Drupal\kwall_alert_system\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides alert resource endpoint
 *
 * @RestResource(
 *   id = "oit_alert_resource",
 *   label = @Translation("Oit Alert Resource"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "canonical" = "/api/oit/v1/alert",
 *     "https://www.drupal.org/link-relations/create" = "/api/oit/v1/alert",
 *   }
 * )
 */
class AlertResources extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('oit_alert_rest'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *
   * @return \Drupal\rest\ResourceResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function post($data) {

    $response_result = [
      'message' => t('Current request could not be processed.'),
      'success' => 0,
      'code' => 400,
      'data' => $data,
    ];

    if (!$this->currentUser->hasPermission('create alert content')) {
      $response_result = [
        'message' => t('User didn\'t have permission to add alert.'),
        'success' => 0,
        'code' => 401,
      ];
    }

    if (!empty($data) && is_array($data) && isset($data['title'])) {

      try {
        $alertNode = Node::create([
          'title' => $data['title'],
          'type' => 'alert',
          'langcode' => 'en',
          'uid' => $this->currentUser->id(),
          'status' => 1,
        ]);

        foreach ($data as $_key => $_value) {
          if ($alertNode->hasField($_key)) {
            if ($_key == 'body') {
              $alertNode->set($_key, [
                'value' => $_value,
                'format' => "full_html",
              ]);
            }
            else {
              $alertNode->set($_key, $_value);
            }
          }
        }

        $alertNode->save();

        $response_result = [
          'message' => t('Alert created successfully.'),
          'success' => 1,
          'code' => 201,
        ];
      } catch (\Exception $e) {

        $response_result = [
          'message' => t('There is some error in creating alert.'),
          'success' => 0,
          'code' => 500,
        ];

      }
    }

    $response = new ResourceResponse(
      $response_result,
      $response_result['code']
    );
    return $response;

  }
}
