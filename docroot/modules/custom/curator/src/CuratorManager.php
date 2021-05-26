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

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;

class CuratorManager {

  /**
   * Current context.
   *
   * @var $context
   */
  protected $context;

  /**
   * Curator logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Curator entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $curatorEntity;

  /**
   * Content entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contentEntity;

  /**
   * Node type entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeType;

  /**
   * Curator Api Manager.
   *
   * @var \Drupal\curator\CuratorApiManager
   */
  protected $curatorApiManager;

  /**
   * Entity field manager object.
   *
   * @var EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * File system object.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * CuratorManager constructor.
   *
   * @param \Drupal\curator\CuratorApiManager $curatorApiManager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(CuratorApiManager $curatorApiManager,
                              LoggerChannelFactoryInterface $channelFactory,
                              EntityTypeManagerInterface $entityTypeManager,
                              EntityFieldManager $entityFieldManager, FileSystemInterface $fileSystem) {
    $this->curatorApiManager = $curatorApiManager;
    $this->logger = $channelFactory->get('curator');
    $this->curatorEntity = $entityTypeManager->getStorage('curator');
    $this->nodeType = $entityTypeManager->getStorage('node_type');
    $this->contentEntity = $entityTypeManager->getStorage('node');

    $this->entityFieldManager = $entityFieldManager;
    $this->fileSystem = $fileSystem;
  }

  /**
   * Set current context
   *
   * @param $context
   *
   * @return $this
   */
  public function setContext($context) {
    $this->context = $context;
    return $this;
  }

  /**
   * Get mapping elements.
   * Function used in injecting setting form with entity form.
   *
   * @param $element
   * @param array $mapping
   * @param string $content_type
   *
   * @return array
   */
  public function getMappingElements($element, $settings, $mapping = [], $content_type = 'article') {
    if (!empty($mapping)) {
      try {

        /** Partial form @var $_form */
        $_form = [];

        foreach ($mapping as $_key => $_type) {

          $_form[$_key] = [
            '#type' => 'select',
            '#title' => new TranslatableMarkup(ucwords(str_replace('_', ' ', $_key))),
            '#options' => $this->getFields($_type, $content_type),
            '#disabled' => FALSE,
            '#default_value' => isset($settings[$_key]) ? $settings[$_key] : '',
          ];

        }
        if (!empty($_form)) {
          $element = array_merge($element, $_form);
        }
      } catch (\Exception $exception) {
        $this->logger->error($exception->getMessage());
      }
    }
    return $element;
  }

  /**
   * Get all content types.
   *
   * @return array
   */
  public function getContentType() {
    $contentTypesList = [];
    // Load all node types.
    $contentTypes = $this->nodeType->loadMultiple();
    if (!empty($contentTypes)) {
      foreach ($contentTypes as $contentType) {
        $contentTypesList[$contentType->id()] = $contentType->label();
      }
    }

    return $contentTypesList;
  }

  /**
   * Get all allowed string|text fields.
   *
   * @param string $type
   * @param string $content_type
   * @param string $entity_type_id
   *
   * @return array
   */
  public function getFields($type = 'text', $content_type = 'article', $entity_type_id = 'node') {
    if ($type == 'text') {
      $allowed_fields = [
        'string',
        'text',
        'text_long',
        'text_with_summary',
        'string_long',
      ];
    }
    else {
      $allowed_fields = [$type];
    }
    $fieldList = [];
    // Get field list
    if (!empty($content_type)) {
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $content_type);
      foreach ($bundle_fields as $field_name => $field_definition) {
        if (in_array($field_definition->getType(), $allowed_fields)) {
          if ($field_definition->getLabel() instanceof TranslatableMarkup) {
            $fieldList[$field_name] = $field_definition->getLabel()->render();
          }
          else {
            $fieldList[$field_name] = $field_definition->getLabel();
          }
        }
      }
    }
    return $fieldList;
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   */
  public function hashTagSlug() {
    return '/hashtag/';
  }

  /**
   * {@inheritdoc}
   */
  public function convertHashTag($network_url, $text = '') {
    if (!empty($network_url) && !empty($text)) {
      // Process hashtag
      $url = $network_url . $this->hashTagSlug();
      $hashtag = $this->getHashtag($text);
      if (is_array($hashtag)) {
        foreach ($hashtag as $tag) {
          $anchor = sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $url . str_replace('#', '', $tag),
            $tag
          );
          $text = str_replace($tag, $anchor, $text);
        }
      }
    }
    return $text;
  }

  /**
   * Return all hash tags of content.
   *
   * @param $text
   *
   * @return array|bool
   */
  protected function getHashtag($text) {
    preg_match_all('/#([\p{Pc}\p{N}\p{L}\p{Mn}]+)/u', $text, $matches);
    return isset($matches[0]) ? $matches[0] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function convertUrl($text = '') {
    $urls = $this->getUrls($text);
    if (is_array($urls)) {
      foreach ($urls as $url) {
        $anchor = sprintf(
          '<a target="_blank" href="%s">%s</a>',
          $this->addhttp($url),
          $url
        );
        $text = str_replace($url, $anchor, $text);
      }
    }

    return $text;
  }

  /**
   * Return all urls of the text.
   *
   * @param $text
   *
   * @return bool
   */
  protected function getUrls($text) {
    preg_match_all('#\b(https?://|www.?)[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $text, $matches);
    return isset($matches[0]) ? $matches[0] : FALSE;
  }

  /**
   * Add http to url.
   *
   * @param $url
   *
   * @return string
   */
  protected function addhttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
      $url = "http://" . $url;
    }
    return $url;
  }

  /**
   * Get all allowed image fields.
   *
   * @param string $content_type
   * @param string $entity_type_id
   *
   * @return array
   */
  public function getImageFields($content_type = 'article', $entity_type_id = 'node') {
    $allowed_fields = [
      'image',
    ];
    $fieldList = [];
    if (!empty($content_type)) {
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $content_type);
      foreach ($bundle_fields as $field_name => $field_definition) {
        if (in_array($field_definition->getType(), $allowed_fields)) {
          if ($field_definition->getLabel() instanceof TranslatableMarkup) {
            $fieldList[$field_name] = $field_definition->getLabel()->render();
          }
          else {
            $fieldList[$field_name] = $field_definition->getLabel();
          }
        }
      }
    }
    return $fieldList;
  }

  /**
   * Get available curator.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public function getAvailableCurator() {
    return $this->curatorEntity->loadByProperties([
      'enable' => 1,
    ]);
  }

  /**
   * Return date to batch process.
   *
   * @return array
   */
  public function getBatchData(CuratorInterface $curator) {
    $data = [];

    // Api query.
    $_data = $this->curatorApiManager
      ->getPosts($curator->getFeedId(), $curator->limit());
    if (!empty($_data)) {
      $data = $data + array_chunk($_data, 5);
    }

    // Return Prepared Data.
    return $data;
  }

  /**
   * Import data to content type.
   *
   * @param $rows
   * @param $curator
   */
  public function import($rows, $curator) {


    if ($curator instanceof CuratorInterface) {

      // Image dir support.
      $image_dir_path = $this->fileSystem
          ->realpath('public://') . '/curator/';
      // Create dir if not exist.
      if (!is_dir($image_dir_path)) {
        mkdir($image_dir_path, 0777, TRUE);
      }

      try {

        // Get mapping array.
        $mappings = $curator->getMappingList();
        $settings = $curator->getSettings();

        // Remove feed lable.
        unset($mappings['feed_label']);
        $feed_label = $settings['feed_label'];
        unset($settings['feed_label']);


        // Loop through all data
        if (!empty($rows)) {
          foreach ($rows as $row) {

            // Check duplicate.
            $duplicate = $this->contentEntity->loadByProperties([
              'type' => $curator->getContentType(),
              $settings['source_identifier'] => $row->source_identifier,
            ]);
            if (!empty($duplicate)) {
              continue;
            }

            // Initialize file to null.
            $file = $media = NULL;

            // Creating node.
            $node = Node::create([
              'type' => $curator->getContentType(),
              'status' => 1,
            ]);

            // Prepare & fill node data.
            foreach ($settings as $key => $setting_key) {
              $field_data = '';

              // Todo: We Can improve this part.
              if (
                $mappings[$key] == 'text' ||
                $mappings[$key] == 'integer'
              ) {
                $field_data = @$row->{$key};
              }
              elseif ($mappings[$key] == 'link') {
                $field_data = [
                  'uri' => @$row->{$key},
                ];
              }
              elseif ($mappings[$key] == 'datetime') {
                $gmtTimezone = new \DateTimeZone('GMT');
                $dp_dt = DrupalDateTime::createFromTimestamp(strtotime($row->{$key}), $gmtTimezone);
                $field_data = $dp_dt->format('Y-m-d\TH:i:s');
              }
              elseif ($mappings[$key] == 'entity_reference' || $mappings[$key] == 'image') {
                if (!empty($row->{$key})) {
                  $remoteFile = $this->getUrlContentsAndFinalUrl($row->{$key});

                  if ($remoteFile !== FALSE) {
                    $fileName = parse_url($row->{$key});
                    $fileName = pathinfo($fileName['path'], PATHINFO_BASENAME);

                    $localFileUri = $this->fileSystem
                      ->saveData($remoteFile,
                        \Drupal::config('system.file')->get('default_scheme')
                        . '://curator/' . $fileName,
                        FileSystemInterface::EXISTS_REPLACE);

                    // Create image file.
                    $localFile = File::create([
                      'uri' => $localFileUri,
                      'uid' => 1,
                      'filename' => $fileName,
                      'status' => 1,
                    ]);
                    $localFile->save();

                    if ($mappings[$key] == 'entity_reference') {
                      $media = Media::create([
                        'bundle' => 'image',
                        'field_media_image' => [
                          'target_id' => $localFile->id(),
                          'alt' => $curator->label(),
                          'title' => $curator->label(),
                        ],
                      ]);
                      $media->save();

                      $field_data = [
                        ['target_id' => $media->id()],
                      ];
                    }
                    else {
                      // Prepare data with alt and title.
                      $field_data = [
                        'target_id' => $localFile->id(),
                        'alt' => $row->feed_name,
                        'title' => $row->feed_name,
                      ];
                    }
                  }
                }
              }

              // Push data to node.
              if ($node->hasField($setting_key)) {
                /**
                 * ToDO: Make it dynamic.
                 */
                if ($setting_key == 'body') {
                  // Convert hash tag and link.
                  $field_data = $this->convertUrl($field_data);
                  // Add to node
                  $node->set($setting_key, [
                    'value' => $field_data,
                    'format' => 'full_html',
                  ]);
                }
                else {
                  $node->set($setting_key, $field_data);
                }
              }
            }

            // Feed site.
            if ($feed_label) {
              $node->set($feed_label, $curator->label());
            }

            // Set node title
            $node->set('title', $row->network_name . ' ' . $row->source_identifier);

            // Set uid and save node.
            $node->set('uid', 1);

            // Save node.
            $node->save();

            // Add file usage.
            if ($file) {
              // ToDo: Inject service instead
              $file_usage = \Drupal::service('file.usage');
              $file_usage->add($file,
                'file',
                $media ? 'media' : 'node',
                $media ? $media->id() : $node->id());
              $file->save();
            }
          }
        }

      } catch (\Exception $e) {
        // Exception.
        $this->logger->notice($e->getMessage());
      }
    }
  }

  /**
   * To get the real URL after file_get_contents even if redirection happens.
   *
   * @param $url
   *
   * @return false|string
   */
  public function getUrlContentsAndFinalUrl(&$url) {
    $url = str_replace(':medium', '', $url);
    do {
      $context = stream_context_create(
        [
          "http" => [
            "follow_location" => FALSE,
          ],
        ]
      );

      $result = file_get_contents($url, FALSE, $context);

      $pattern = "/^Location:\s*(.*)$/i";
      $location_headers = preg_grep($pattern, $http_response_header);

      if (!empty($location_headers) &&
        preg_match($pattern, array_values($location_headers)[0], $matches)) {
        $url = $matches[1];
        $repeat = TRUE;
      }
      else {
        $repeat = FALSE;
      }
    } while ($repeat);

    return $result;
  }

  /**
   * Independent function for running the post importer,
   * Function used in cron and light weight cron.
   * Instead of running batch process this will directly
   * import the post.
   */
  public function runImporter() {
    // Get all available curator.
    $curators = $this->getAvailableCurator();
    if ($curators) {
      foreach ($curators as $_id => $curator) {
        // Get data from api and prepare for batch.
        $data = $this->getBatchData($curator);
        // Add operation to batch process.
        if (!empty($data)) {
          foreach ($data as $key => $_data) {
            // Import data to content type.
            try {
              $this->import(
                $_data,
                $curator
              );
            } catch (\Exception $e) {
              // Exception handling.
              $this->logger->notice($e->getMessage());
            }
          }
        }
      }
    }
  }

}
