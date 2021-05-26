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

namespace Drupal\curator\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\curator\CuratorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ExternalCronController.
 *
 * @package Drupal\curator\Controller
 */
class ExternalCronController extends ControllerBase {

  /**
   * Curator settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $curatorSettings;

  /**
   * Curator manager object.
   *
   * @var \Drupal\curator\CuratorManager
   */
  protected $curatorManager;

  /**
   * ExternalCronController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\curator\CuratorManager $curatorManager
   */
  public function __construct(ConfigFactoryInterface $configFactory,
                              CuratorManager $curatorManager) {
    $this->curatorSettings = $configFactory->getEditable('curator.settings');
    $this->curatorManager = $curatorManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('curator.manager')
    );
  }

  /**
   * Run cron from external source.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function run(Request $request) {

    $message = [
      "code" => 401,
      "message" => 'You are not authorize to access cron.',
    ];

    $cron_key = $this->curatorSettings->get('cron_key');
    if (!empty($cron_key)) {
      $external_key = $request->get('key');
      // Match the key.
      if ($cron_key == $external_key) {
        // Run importer.
        $this->curatorManager->runImporter();
        // Change message.
        $message = [
          "code" => 200,
          "message" => 'Cron successfully completed.',
        ];
      }
    }

    // Return json response.
    return new JsonResponse($message, $message['code']);
  }

}
