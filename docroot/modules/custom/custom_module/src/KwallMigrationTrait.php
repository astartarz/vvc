<?php

namespace Drupal\custom_module;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\filter\FilterPluginCollection;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\redirect\Entity\Redirect;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Exception\RequestException;
use HTMLPurifier;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Trait KwallMigrationTrait.
 *
 * @package Drupal\custom_module
 */
trait KwallMigrationTrait {

  /**
   * Store info about the basic field for each media bundle.
   *
   * @var
   */
  protected $mediaFieldsMapper;

  /**
   * Store info about the path of the file for each media bundle.
   *
   * @var
   */
  protected $mediaFilePathMapper;


  /**
   * Store an info, the data was migrated from.
   *
   * @var
   */
  protected $migratedData;

  /**
   * Store an info about current domain.
   *
   * @var
   */
  protected $currentDomain;

  /**
   * Store an info about current full URL.
   *
   * @var
   */
  protected $currentRemoteUrl;

  /**
   * Store an info about title from <title></title>.
   *
   * @var
   */
  protected $currentHtmlTitle;

  /**
   * Store an info about current Department.
   *
   * @var
   */
  protected $currentDepartment;

  /**
   * Store an info about current Page ID.
   *
   * @var
   */
  protected $currentPageID;

  /**
   * Store an info about mapping of the links to the new structure of the site.
   *
   * @var
   */
  protected $newStructureLinksMapper;

  /**
   * Allow to migrate files with the same path as were on previous site.
   *
   * @var
   */
  protected $useLegacyPathForFiles;

  /**
   * Store an ifo about legacy path for a file.
   *
   * @var
   */
  protected $legacyPath;

  /**
   * Describe all basic field for each media bundle.
   */
  public function setMediaFields() {
    $this->mediaFieldsMapper = [
      'article_featured_image' => 'field_media_image_9',
      'audio' => 'field_media_audio_file',
      'document' => 'field_media_file',
      'file' => 'field_media_file',
      'gallery_carousel' => 'field_images',
      'header_image' => 'field_media_image',
      'image' => 'field_media_image',
      'logo' => 'field_media_image',
      'pdf_document' => 'field_media_file',
      'portrait' => 'field_media_image',
      'remote_video' => 'field_media_oembed_video',
      'person_profile_image' => 'field_media_image_6',
    ];
  }

  /**
   * Get field name of a media by bundle.
   *
   * @param $bundle
   *
   * @return mixed
   */
  public function getMediaFiledNameByBundle($bundle) {
    $this->setMediaFields();

    return $this->mediaFieldsMapper[$bundle];
  }

  /**
   * Describe all paths of the files for each media bundle.
   */
  public function setFilePath() {
    $this->mediaFilePathMapper = [
      'article_featured_image' => 'public://media/article_featured_image',
      'audio' => 'public://media/audio',
      'document' => 'public://media/document',
      'file' => 'public://media/file',
      'gallery_carousel' => 'public://academic_department_banners',
      'header_image' => 'public://media/header_image',
      'image' => 'public://media/images',
      'logo' => 'public://media/logo',
      'pdf_document' => 'public://media/pdf_document',
      'portrait' => 'public://media/portrait',
      // Just a stub, the videos are remote.
      'remote_video' => 'public://videos',
      'person_profile_image' => 'public://media/person_profile_image',
    ];
  }

  /**
   * Override the paths in some cases.
   *
   * @param $bundle
   * @param $path
   */
  public function overrideFilePath($bundle, $path) {
    $this->mediaFilePathMapper[$bundle] = 'public:/' . $path;

    return $this->mediaFilePathMapper;
  }

  /**
   * Get the file path of the a media by bundle.
   *
   * @param string $bundle
   *
   * @return mixed
   */
  public function getMediaFilePathByBundle($bundle = 'image') {
    if ($this->mediaFilePathMapper === NULL) {
      $this->setFilePath();
    }

    return $this->mediaFilePathMapper[$bundle];
  }

  /**
   * Set Migrated Data.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $migratedData
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   */
  public function setMigratedData(Paragraph $migratedData) {
    $this->migratedData = $migratedData;

    return $this->migratedData;
  }

  /**
   * Get Migrated Data.
   *
   * @return mixed
   */
  public function getMigratedData() {
    return $this->migratedData;
  }

  /**
   * Set current domain.
   *
   * @param string $domain
   *
   * @return string
   */
  public function setCurrentDomain(string $domain) {
    $this->currentDomain = $domain;

    return $this->currentDomain;
  }

  /**
   * Get current domain.
   *
   * @return mixed
   */
  public function getCurrentDomain() {
    return $this->currentDomain;
  }

  /**
   * Set current remote URL.
   *
   * @param string $url
   *
   * @return string
   */
  public function setCurrentRemoteUrl(string $url) {
    $this->currentRemoteUrl = $url;

    return $this->currentRemoteUrl;
  }

  /**
   * Get current remote URL.
   *
   * @return mixed
   */
  public function getCurrentRemoteUrl() {
    return $this->currentRemoteUrl;
  }

  /**
   * Set current HTML title.
   *
   * @param string $title
   *
   * @return string
   */
  public function setCurrentHtmlTitle(string $title) {
    $this->currentHtmlTitle = $title;

    return $this->currentHtmlTitle;
  }

  /**
   * Get current HTML title.
   *
   * @return mixed
   */
  public function getCurrentHtmlTitle() {
    return $this->currentHtmlTitle;
  }

  /**
   * Set current Department.
   *
   * @param string $department
   *
   * @return string
   */
  public function setCurrentDepartment(string $department) {
    $this->currentDepartment = $department;

    return $this->currentDepartment;
  }

  /**
   * Get current Department.
   *
   * @return mixed
   */
  public function getCurrentDepartment() {
    return $this->currentDepartment;
  }

  /**
   * Set current Page ID.
   *
   * @param string $pageID
   *
   * @return string
   */
  public function setCurrentPageID(string $pageID) {
    $this->currentPageID = $pageID;

    return $this->currentPageID;
  }

  /**
   * Get current Page ID.
   *
   * @return mixed
   */
  public function getCurrentPageID() {
    return $this->currentPageID;
  }

  /**
   * Set new structure of the links for tne site.
   *
   * @return array
   */
  public function setNewStructureLinksMapper() {
    $this->newStructureLinksMapper = [
      'www.vcccd.edu' => '/',
      'www.moorparkcollege.edu' => '/',
      'www.oxnardcollege.edu' => '/',
      'www.venturacollege.edu' => '/',
      'drupal.vcccd.edu' => '/',
    ];

    return $this->newStructureLinksMapper;
  }

  /**
   * Get new structure of the links for tne site.
   *
   * @return array
   */
  public function getNewStructureLinksMapper() {
    return $this->setNewStructureLinksMapper();
  }

  /**
   * Set TRUE if the script should use legacy path
   * and ignore the paths described in each media type.
   *
   * @return bool
   */
  public function setUseLegacyPathForFiles() {
    $this->useLegacyPathForFiles = TRUE;

    return $this->useLegacyPathForFiles;
  }

  /**
   * Get the info, should use the paths as is
   * or should be replaced to the paths described in each media type.
   *
   * @return bool
   */
  public function useLegacyPathForFiles() {
    return ($this->useLegacyPathForFiles === TRUE);
  }

  /**
   * Set a legacy path of the current migrate file.
   *
   * @param string $path
   *
   * @return string
   */
  public function setLegacyPath(string $path) {
    $this->legacyPath = $path;

    return $this->legacyPath;
  }

  /**
   * Get a legacy path of the current migrate file.
   *
   * @return string
   */
  public function getLegacyPath() {
    return (string) $this->legacyPath;
  }

  /**
   * Prints Debug info.
   *
   * @param $v
   */
  public static function d($v) {
    if (function_exists('dump')) {
      dump($v);
    }
    else {
      print_r($v);
      print "\n";
    }
  }

  /**
   * Checks if the string is HTML.
   *
   * @param $string
   *
   * @return bool
   */
  public function isHTML($string) {
    return ($string != strip_tags($string));
  }

  /**
   * Checks if the string is PDF.
   *
   * @param $string
   *
   * @return bool
   */
  public function isPDF($string) {
    if (preg_match("/^%PDF-1/", $string)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Load remote page.
   *
   * @param $url
   *
   * @return bool|\Symfony\Component\DomCrawler\Crawler
   */
  public function loadContentFromRemove($url) {
    $ctx = stream_context_create(['http' => ['timeout' => 360]]);
    $html = @file_get_contents($url, FALSE, $ctx);

    if ($html === FALSE || !$this->isHTML($html) || $this->isPDF($html)) {
      return FALSE;
    }

    return new Crawler($html, $url);
  }

  /**
   * Create file.
   *
   * @param $bundle
   * @param $remoteUrl
   * @param $fileName
   *
   * @return \Drupal\file\FileInterface|false|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createFile($bundle, $remoteUrl, $fileName) {
    $originalRemoteUrl = $remoteUrl;
    $file = NULL;

    // Fix domain.
    $remoteUrl = $this->fixDomain($remoteUrl);

    // Remove image styles from URL and get the link to the source file.
    // Helpful for images.
    $remoteUrl = $this->extractRealImagePath($remoteUrl);

    // For BSU project we should to leave the original paths for the files.
    if ($this->useLegacyPathForFiles()) {
      $parsedUrl = parse_url($remoteUrl);
      $parsedPath = $parsedUrl['path'];
      $parsedPath = str_replace([
        '/sites/default/files/',
        $fileName,
      ], '', $parsedPath);
      $parsedPath = trim($parsedPath, '/');
      $destination = 'public://' . $parsedPath;
    }
    else {
      $destination = $this->getMediaFilePathByBundle($bundle);
    }

    // TODO: Check if the file exists.
    //  For example already was uploaded by SFTP.
    // if (!file_exists($file->getFileUri())) { ... blablabla...

    //    $remoteUrl = str_replace(' ', '%20', $remoteUrl);
    //    $remoteUrl = urlencode($remoteUrl);

    if (\Drupal::service('file_system')
      ->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      set_time_limit(0);

      // FIXME: Remove or comment PDF placeholder after testing.
      /*
      $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
      if ($ext == 'pdf') {
        $remoteUrl = __DIR__ . '/../assets/pdf-placeholder.pdf';
      }
      */
      // FIXME: End of the removing.

      $fileContents = @file_get_contents($remoteUrl);
      if (!empty($fileContents)) {
        // $fileName = str_replace('%20', ' ', $fileName);
        $fileName = urldecode($fileName);
        $newDestination = $destination . '/' . $fileName;
        if ($file = file_save_data($fileContents, $newDestination, FileSystemInterface::EXISTS_REPLACE)) {
          // Create a redirect.
          // We need to create a redirect only in cases if we change the URI.
          // For BSU project, we should have the same paths.
          if (!$this->useLegacyPathForFiles()) {
            $this->createRedirectForFile($originalRemoteUrl, $file);
          }

          return $file;
        }
      }
    }

    return $file;
  }

  /**
   * Create or get media entity.
   *
   * @param $bundle
   * @param $absoluteUrl
   * @param array $attributes
   * @param array $migratedData
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|\Drupal\media\Entity\Media|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
    public function createMedia($bundle, $absoluteUrl, $attributes = [], $migratedData = []) {
    $parsedUrl = parse_url($absoluteUrl);
    if ($bundle != 'person_profile_image') {
      $fileName = pathinfo($parsedUrl['path'], PATHINFO_BASENAME);
    }
    else {
      $fileName = $attributes['filename'];
    }

    // Check if media already exists.
    if ($this->useLegacyPathForFiles()) {
      $parsedPath = $parsedUrl['path'];
      $parsedPath = $this->extractRealImagePath($parsedPath);
      // FIXME: Replace "default" if necessary.
      $parsedPath = str_replace([
        '/sites/default/files/',
        $fileName,
      ], '', $parsedPath);
      $parsedPath = trim($parsedPath, '/');
      $realFullPath = 'public://' . $parsedPath . '/' . $fileName;
      $media = $this->findMediaByFullPath($realFullPath, $bundle);
    }
    else {
      $media = $this->findMediaByFileName($fileName, $bundle);
    }

    // Check if a media already exists. If not, create a new one.
    if (!$media instanceof MediaInterface) {
      // Create a file and media.
      $file = $this->createFile($bundle, $absoluteUrl, $fileName);
      if ($file instanceof FileInterface) {

        $mediaFieldName = $this->getMediaFiledNameByBundle($bundle);

        $replacement = [
          '""' => '',
          '%20' => ' ',
        ];

        $data = [
          'target_id' => $file->id(),
        ];

        // Media name.
        $mediaName = $file->getFilename();

        $data['title'] = $mediaName;
        if (isset($attributes['title']) && !empty($attributes['title'])) {
          $data['title'] = Unicode::truncate(str_replace(array_keys($replacement), array_values($replacement), $attributes['title']), 60);
        }

        $data['alt'] = $mediaName;
        if (isset($attributes['alt']) && !empty($attributes['alt'])) {
          $data['alt'] = Unicode::truncate(str_replace(array_keys($replacement), array_values($replacement), $attributes['alt']), 60);
        }

        $data['description'] = $mediaName;
        if (isset($attributes['description']) && !empty($attributes['description'])) {
          $data['description'] = Unicode::truncate(str_replace(array_keys($replacement), array_values($replacement), $attributes['description']), 60);
        }

        $media = Media::create([
          'bundle' => $bundle,
          'name' => $mediaName,
          'uid' => \Drupal::currentUser()->id(),
          'langcode' => \Drupal::languageManager()
            ->getDefaultLanguage()
            ->getId(),
          'status' => 1,
        ]);

        // Attach the file to the media.
        $media->set($mediaFieldName, $data);

        // Extract categories from file path.
        if ($bundle != 'person_profile_image') {
          if (strpos($absoluteUrl, '/files/') === FALSE) {
            $fileTags = $this->extractCategoriesFromFlePath($absoluteUrl);
          }
          if (!empty($fileTags)) {
            $terms = $this->createTerms('media_category', $fileTags);
            $media->set('field_categories', $terms);
          }
        }

        // Add info about migrated from.
        if (empty($migratedData)) {
          $migratedData = $this->getMigratedData();
        }
        if (!empty($migratedData)) {
          $media->set('field_migrated_data', $migratedData);
        }

        $media->save();
      }
    }

    return $media;
  }

  /**
   * Create remote video.
   *
   * @param $url
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createVideoMedia($url) {
    $bundle = 'remote_video';
    $mediaFieldName = $this->getMediaFiledNameByBundle($bundle);

    $media = Media::create([
      'bundle' => $bundle,
      'uid' => \Drupal::currentUser()->id(),
      'langcode' => \Drupal::languageManager()
        ->getDefaultLanguage()
        ->getId(),
      'status' => 1,
    ]);

    $media->set($mediaFieldName, $url);
    $media->save();

    return $media;
  }

  /**
   * Find a remote video media.
   *
   * @param $url
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findVideoMediaByUrl($url) {
    $bundle = 'remote_video';
    $mediaFieldName = $this->getMediaFiledNameByBundle($bundle);

    $params = [
      $mediaFieldName => $url,
    ];

    // Load the entities by the value of the field.
    $medias = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->loadByProperties($params);

    if (!empty($medias)) {
      return array_pop($medias);
    }

    return [];
  }

  /**
   * Find file by name.
   *
   * @param $filename
   * @param $bundle
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|mixed
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findFileByName($filename, $bundle) {
    $mediaFilePath = $this->getMediaFilePathByBundle($bundle);
    //    $filename = str_replace('%20', ' ', $filename);
    $filename = urldecode($filename);

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $mediaFilePath . '/' . $filename]);

    if (!empty($files)) {
      return array_pop($files);
    }

    return [];
  }

  /**
   * Find file by path.
   *
   * @param $path
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findFileByFullPath($path) {
    $path = urldecode($path);

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $path]);

    if (!empty($files)) {
      return array_pop($files);
    }

    return [];
  }

  /**
   * Get UUID of the file.
   *
   * @param $filename
   * @param $bundle
   *
   * @return array|string|null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFileUuidByFileName($filename, $bundle) {
    $file = $this->findFileByName($filename, $bundle);

    if (!empty($file)) {
      return $file->uuid();
    }

    return [];
  }

  /**
   * Find media by file name.
   *
   * @param $filename
   * @param string $bundle
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|mixed
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findMediaByFileName($filename, $bundle) {
    $mediaFieldName = $this->getMediaFiledNameByBundle($bundle);
    $file = $this->findFileByName($filename, $bundle);

    if (!empty($file)) {
      $params = [
        'bundle' => $bundle,
        'status' => 1,
      ];

      $field_definitions = \Drupal::service('entity_field.manager')
        ->getFieldDefinitions('media', $bundle);

      // Check if the field exists in the $entity_type.
      if (array_key_exists($mediaFieldName, $field_definitions)) {
        // And add the value of the field as a parameter.
        $params[$mediaFieldName] = $file->id();

        // Load the entities by the value of the field.
        $medias = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->loadByProperties($params);

        if (!empty($medias)) {
          return array_pop($medias);
        }

      }
    }

    return [];
  }

  /**
   * Find media by file full path.
   *
   * @param $path
   * @param $bundle
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findMediaByFullPath($path, $bundle) {
    $mediaFieldName = $this->getMediaFiledNameByBundle($bundle);
    $file = $this->findFileByFullPath($path);

    if (!empty($file)) {
      $params = [
        'bundle' => $bundle,
        'status' => 1,
      ];

      $field_definitions = \Drupal::service('entity_field.manager')
        ->getFieldDefinitions('media', $bundle);

      // Check if the field exists in the $entity_type.
      if (array_key_exists($mediaFieldName, $field_definitions)) {
        // And add the value of the field as a parameter.
        $params[$mediaFieldName] = $file->id();

        // Load the entities by the value of the field.
        $medias = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->loadByProperties($params);

        if (!empty($medias)) {
          return array_pop($medias);
        }

      }
    }

    return [];
  }

  /**
   * Get UUID of the media by bundle.
   *
   * @param $filename
   * @param string $bundle
   *
   * @return string|null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMediaUuidByFileName($filename, $bundle) {
    $media = $this->findMediaByFileName($filename, $bundle);
    if (!empty($media)) {
      return $media->uuid();
    }

    return '';
  }

  /**
   * Get UUID of the media by file path.
   *
   * @param $path
   * @param $bundle
   *
   * @return string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMediaUuidByFullPath($path, $bundle) {
    $media = $this->findMediaByFullPath($path, $bundle);
    if (!empty($media)) {
      return $media->uuid();
    }

    return '';
  }

  /**
   * Create Migrated Data Paragraph.
   *
   * @param $id
   * @param $title
   * @param $url
   * @param $contentType
   * @param $menuName
   * @param $menuItem
   * @param $menuStatus
   * @param $menuWeight
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\paragraphs\Entity\Paragraph
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createMigratedDataParagraph($id, $title, $url, $contentType, $menuName, $menuItem, $menuStatus, $menuWeight) {
    $data = [
      'field_original_id' => $id, // Legacy Node ID.
      'field_original_title' => $title, // Legacy Page Title.
      'field_original_url' => $url, // Legacy Path.
      'field_subtext' => $contentType, // Legacy Content Type.
      'field_legacy_menu_name' => $menuName, // Legacy Menu Name.
      'field_legacy_menu_item' => $menuItem, // Legacy Menu Item.
      'field_legacy_menu_status' => $menuStatus, // Legacy Menu Item Status.
      'field_legacy_menu_weight' => $menuWeight, // Legacy Menu Item Weight.
      'field_is_migrated' => 1, // Is migrated.
    ];

    return $this->createParagraph('migrated_data', $data);
  }

  /**
   * Create a Paragraph.
   *
   * @param $bundle
   * @param $data
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\paragraphs\Entity\Paragraph
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createParagraph($bundle, $data) {
    $paragraph = Paragraph::create([
      'type' => $bundle,
    ]);
    foreach ($data as $field => $value) {
      $paragraph->set($field, $value);
    }
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Get term by name.
   *
   * @param null $name
   * @param null $vid
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTermByName($name = NULL, $vid = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    $term = reset($terms);

    return !empty($term) ? $term : FALSE;
  }

  /**
   * Get term by params.
   *
   * @param array $properties
   * @param null $vid
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTermByParams($properties = [], $vid = NULL) {
    if ($vid !== NULL) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    $term = reset($terms);

    return !empty($term) ? $term : FALSE;
  }

  /**
   * Utility: find term by name and vid.
   *
   * @param null $name
   * @param null $vid
   *
   * @return bool|int|string|null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */

  protected function getTidByName($name = NULL, $vid = NULL) {
    $term = $this->getTermByName($name, $vid);

    return !empty($term) ? $term->id() : FALSE;
  }

  /**
   * Create the terms.
   *
   * @param $vid
   * @param array $values
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createTerms($vid, $values = []) {
    $terms = [];

    foreach ($values as $value) {
      if ($value != '') {
        $term = $this->getTidByName($value, $vid);
        if ($term === FALSE) {
          $term = Term::create([
            'parent' => [],
            'name' => $value,
            'vid' => $vid,
          ]);

          $term->save();
        }
        $terms[] = $term;
      }

    }

    return $terms;
  }

  /**
   * Download assets.
   * Replace inline images and some files with medias.
   *
   * @param $html
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function assets2Media($html) {
    // Find all images.
    preg_match_all('/<img[^>]+>/i', $html, $result);

    if (!empty($result[0])) {

      foreach ($result as $img_tags) {

        foreach ($img_tags as $img_tag) {
          // For debugging.
          // $this::d($img_tag);

          $doc = new \DOMDocument();
          $doc->loadHTML(mb_convert_encoding($img_tag, 'HTML-ENTITIES', 'UTF-8'));

          // Just to make xpath more simple.
          $xml = simplexml_import_dom($doc);
          $image = $xml->xpath('//img')[0];
          $attributes = [
            'src' => (string) $image['src'],
            'alt' => (string) $image['alt'],
            'title' => (string) $image['title'],
            'class' => (string) $image['class'],
            'style' => (string) $image['style'],
            'align' => (string) $image['align'],
          ];

          if (!empty($attributes['src']) && strpos($attributes['src'], 'data:image/') === FALSE) {
            // Add protocol for links starting with //.
            $attributes['src'] = $this->addHttps($attributes['src']);
            // Replace the special chars.
            $filePath = str_replace('"', '', $attributes['src']);
            $filePath = str_replace('&amp;', '&', $filePath);

            if (!filter_var($filePath, FILTER_VALIDATE_URL)) {
              // For relative links needed add the domain.
              $filePath = $this->getCurrentDomain() . $filePath;
            }

            $uuid = FALSE;
            if ($this->isInternalLink($filePath)) {
              $media = $this->createMedia('image', $filePath, $attributes);
              if ($media instanceof MediaInterface) {
                // Set UUID.
                $parsedUrl = parse_url($filePath);
                $fileName = pathinfo($parsedUrl['path'], PATHINFO_BASENAME);
                if ($this->useLegacyPathForFiles()) {
                  $parsedPath = $parsedUrl['path'];
                  $parsedPath = $this->extractRealImagePath($parsedPath);
                  $parsedPath = str_replace([
                    '/sites/default/files/',
                    $fileName,
                  ], '', $parsedPath);
                  $parsedPath = trim($parsedPath, '/');
                  $realFullPath = 'public://' . $parsedPath . '/' . $fileName;
                  $uuid = $this->getMediaUuidByFullPath($realFullPath, 'image');
                }
                else {
                  $uuid = $this->getMediaUuidByFileName($fileName, 'image');
                }
              }
              if ($uuid) {
                if ($attributes['class'] !== '' || $attributes['style'] !== '' || $attributes['align'] !== '') {

                  // Look also "Move some HTML/CSS properties and classes to another place" section at this file.

                  $align = '';
                  if (strpos($attributes['style'], 'left;') !== FALSE) {
                    $align = 'data-align="left"';
                  }
                  if (strpos($attributes['style'], 'right;') !== FALSE) {
                    $align = 'data-align="right"';
                  }
                  if (strpos($attributes['style'], 'center;') !== FALSE) {
                    $align = 'data-align="center"';
                  }
                  if (strpos($attributes['style'], 'margin-left: auto;') !== FALSE
                    && strpos($attributes['style'], 'margin-right: auto;') !== FALSE) {
                    $align = 'data-align="center"';
                  }
                  if (strpos($attributes['style'], 'margin-left:auto;') !== FALSE
                    && strpos($attributes['style'], 'margin-right:auto;') !== FALSE) {
                    $align = 'data-align="center"';
                  }
                  if ($attributes['align'] == 'left') {
                    $align = 'data-align="left"';
                  }
                  if ($attributes['align'] == 'right') {
                    $align = 'data-align="right"';
                  }
                  if ($attributes['align'] == 'center') {
                    $align = 'data-align="center"';
                  }
                  if (strpos($attributes['class'], 'pull-left') !== FALSE) {
                    $align = 'data-align="left"';
                  }
                  if (strpos($attributes['class'], 'pull-center') !== FALSE
                    || strpos($attributes['class'], 'center-block') !== FALSE) {
                    $align = 'data-align="center"';
                  }
                  if (strpos($attributes['class'], 'pull-right') !== FALSE) {
                    $align = 'data-align="right"';
                  }
                  if (strpos($attributes['class'], 'photostyle-left') !== FALSE) {
                    $align = 'data-align="left"';
                  }
                  if (strpos($attributes['class'], 'photostyle-center') !== FALSE) {
                    $align = 'data-align="center"';
                  }
                  if (strpos($attributes['class'], 'photostyle-right') !== FALSE) {
                    $align = 'data-align="right"';
                  }
                  if (strpos($attributes['class'], 'graphic-left') !== FALSE) {
                    $align = 'data-align="left"';
                  }
                  if (strpos($attributes['class'], 'graphic-center') !== FALSE) {
                    $align = 'data-align="center"';
                  }
                  if (strpos($attributes['class'], 'graphic-right') !== FALSE) {
                    $align = 'data-align="right"';
                  }
                  if ($attributes['alt'] == 'Image of a pie chart') {
                    $align = 'data-align="left"';
                  }

                  // Remove some properties.
                  $attributes['style'] = trim(preg_replace('/border:(.+);(\s+)?/U', '', $attributes['style']));
                  $attributes['style'] = trim(preg_replace('/margin(.+);(\s+)?/U', '', $attributes['style']));

                  // Add "img-wrapper" for some special cases.
                  if (strpos($attributes['style'], 'width') !== FALSE
                    || strpos($attributes['style'], 'height') !== FALSE) {
                    $attributes['class'] .= ' img-wrapper';
                    $attributes['class'] = trim($attributes['class']);
                    $attributes['style'] = str_replace('width', 'max-width', $attributes['style']);
                    $attributes['style'] = str_replace('max-max-width', 'max-width', $attributes['style']);
                  }

                  $html = str_replace($img_tag, '<div class="' . $attributes['class'] . '"  style="' . $attributes['style'] . '"><drupal-entity
                  ' . $align . '
                    data-embed-button="media_entity_embed"
                    data-entity-embed-display="view_mode:media.full"
                    data-entity-embed-display-settings="{&quot;link_url&quot;:&quot;&quot;,&quot;link_url_target&quot;:0}"
                    data-entity-type="media"
                    data-entity-uuid="' . $uuid . '"></drupal-entity></div>', $html);
                }
                else {
                  $html = str_replace($img_tag, '<drupal-entity
                    data-embed-button="media_entity_embed"
                    data-entity-embed-display="view_mode:media.full"
                    data-entity-embed-display-settings="{&quot;link_url&quot;:&quot;&quot;,&quot;link_url_target&quot;:0}"
                    data-entity-type="media"
                    data-entity-uuid="' . $uuid . '"></drupal-entity>', $html);
                }
              }
            }
            else {
              // TODO: Nothing for now, however maybe we should transform
              //  'data-align="left"' and 'data-align="right"' too.
            }
          }
        }
      }
    }

    // Find all Assets (Images, PDF and etc) in the links.
    $doc = new \DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    $links = $doc->getElementsByTagName('a');
    foreach ($links as $link) {

      $newPath = '';
      $currentUrl = trim($link->getAttribute('href'));
      // Add protocol for links starting with //.
      $currentUrl = $this->addHttps($currentUrl);

      // Replace the special chars.
      $currentUrl = str_replace('"', '', $currentUrl);
      $currentUrl = str_replace('&amp;', '&', $currentUrl);
      $currentFilename = basename($currentUrl);
      $ext = strtolower(pathinfo($currentFilename, PATHINFO_EXTENSION));

      if (filter_var($currentUrl, FILTER_VALIDATE_URL)) {
        $oldLink = $currentUrl;
      }
      else {
        // For relative links needed add the domain.
        $oldLink = $this->getCurrentDomain() . $currentUrl;
      }

      if ($this->isInternalLink($currentUrl)) {
        $attributes = [
          'alt' => $link->getAttribute('title'),
          'title' => $currentFilename,
          'description' => $link->getAttribute('title'),
        ];

        $imageExtensionMapper = 'png, gif, jpg, jpeg, bmp, svg';
        $imageExtensionMapper = explode(', ', $imageExtensionMapper);

        if (in_array($ext, $imageExtensionMapper)) {
          $media = $this->createMedia('image', $oldLink, $attributes);
          if ($media instanceof MediaInterface) {
            // Get real path to the file.
            $fid = $media->get('field_media_image')
              ->getValue()[0]['target_id'];
            $file = File::load($fid);
            $newPath = file_url_transform_relative(file_create_url($file->getFileUri()));
          }

          // Replace link to the file.
          if ($newPath !== '') {
            $link->setAttribute('href', $newPath);
          }

        }

        if ($ext == 'pdf') {
          $media = $this->createMedia('pdf_document', $oldLink, $attributes);

          if ($media instanceof MediaInterface) {
            // Get real path to the file.
            $fid = $media->get('field_media_file')->getValue()[0]['target_id'];
            $file = File::load($fid);
            $newPath = file_url_transform_relative(file_create_url($file->getFileUri()));
          }

          // Replace link to the file.
          if ($newPath !== '') {
            $link->setAttribute('href', $newPath);
          }
        }

        $fileExtensionMapper = 'txt, rtf, doc, docx, ppt, pptx, xls, xlsx, odf, odg, odp, ods, odt, fodt, fods, fodp, fodg, key, numbers, pages, dwg';
        $fileExtensionMapper = explode(', ', $fileExtensionMapper);
        if (in_array($ext, $fileExtensionMapper)) {
          $media = $this->createMedia('document', $oldLink, $attributes);
          if ($media instanceof MediaInterface) {
            // Get real path to the file.
            $fid = $media->get('field_media_file')->getValue()[0]['target_id'];
            $file = File::load($fid);
            $newPath = file_url_transform_relative(file_create_url($file->getFileUri()));
          }

          // Replace link to the file.
          if ($newPath !== '') {
            $link->setAttribute('href', $newPath);
          }
        }
      }
    }

    $html = $doc->saveHTML();

    $html = $this->removeHtmlAndBodyTags($html);

    return $html;
  }

  /**
   * Remove JavaScript and CSS Styles.
   *
   * @param string $html
   * @param bool $removeJS
   *
   * @return mixed|string|string[]|null
   */
  public function cleanUpHtml(string $html, $removeJS = TRUE) {
    $purifier = new HTMLPurifier();
    $html = str_replace('<?xml version="1.0" encoding="utf-16"?>', '', $html);
    if ($removeJS === TRUE) {
      $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
    }

    // Remove commented HTML.
    $html = $this->removeHtmlComments($html);

    // Replace multiple whitespace characters with a single space.
    $html = trim(preg_replace('/\s+/', ' ', $html));
    // Remove 'NO-BREAK SPACE' symbol.
    $html = str_replace(' ', ' ', $html);

    // HTMLPurifier removes empty tags and it broke fontawesome icons.
    $html = str_replace('></em>', ">&nbsp;</em>", $html);
    $html = str_replace('></span>', ">&nbsp;</span>", $html);

    $purifier->config->set('Core.Encoding', 'UTF-8');
    $purifier->config->set('HTML.Doctype', 'HTML 4.01 Transitional');
    $purifier->config->set('Cache.SerializerPath', file_directory_temp());
    $purifier->config->set('Attr.EnableID', TRUE);
    $purifier->config->set('AutoFormat.RemoveEmpty', TRUE);
    $purifier->config->set('AutoFormat.RemoveEmpty.RemoveNbsp', TRUE);
    $purifier->config->set('AutoFormat.RemoveEmpty.RemoveNbsp.Exceptions', [
      'em' => TRUE,
      'span' => TRUE,
      'td' => TRUE,
      'th' => TRUE,
    ]);

    $purifier->config->set('HTML.Trusted', TRUE);

    // $purifier->config->set('AutoFormat.RemoveSpansWithoutAttributes', TRUE);
    // $purifier->config->set('CSS.AllowedProperties', []);
    // $purifier->config->set('CSS.Trusted', TRUE);
    /*
    $purifier->config->set('CSS.AllowedProperties', [
      'background-color'
    ]);
    */
    /*
    $purifier->config->set('CSS.AllowedFonts', [
      'Montserrat-Light',
      'Open Sans',
    ]);
    */

    // Allowed Elements in HTML
    $HTML_Allowed_Elms = 'a, abbr, acronym, b, blockquote, br, caption, cite, code, dd, del, dfn, div, dl, dt, em, font, h1, h2, h3, h4, h5, h6, i, img, ins, kbd, li, ol, p, pre, s, span, strike, strong, sub, sup, table, tbody, td, tfoot, th, thead, tr, tt, u, ul, var, iframe, center, hr';
    // Allowed Element Attributes in HTML, element must also be allowed in Allowed Elements for these attributes to work.
    $HTML_Allowed_Attr = 'a.href, a.rev, a.title, a.class, a.target, a.rel, a.data-chrome, a.data-link-color, a.data-tweet-limit, a.id, a.name, abbr.title, acronym.title, blockquote.cite, div.align, div.style, div.class, div.id, em.class, font.size, font.color, h1.style, h1.class, h2.style, h2.class, h3.style, h3.class, h4.style, h4.class, h5.style, h5.class, h6.style, h6.class, img.src, img.alt, img.title, img.class, img.align, img.style, img.height, img.width, ol.class, ol.style, p.style, p.class, p.align, span.style, span.class, span.id, .class, table.align, table.id, table.border, table.cellpadding, table.cellspacing, table.style, table.width, table.dir, td.abbr, td.align, td.class, td.id, td.colspan, td.rowspan, td.style, td.valign, td.width, td.height, tr.align, tr.class, tr.id, tr.style, tr.valign, tr.bgcolor, th.abbr, th.align, th.class, th.id, th.colspan, th.rowspan, th.style, th.valign, ul.class, ul.style, iframe.src, iframe.width, iframe.height, iframe.frameborder, iframe.scrolling, iframe.class, iframe.title, iframe.style, iframe.align, li.class';
    // Sets allowed html elements that can be used.
    $purifier->config->set('HTML.AllowedElements', $HTML_Allowed_Elms);
    // Sets allowed html attributes that can be used.
    $purifier->config->set('HTML.AllowedAttributes', $HTML_Allowed_Attr);

    // Allow YouTube and Vimeo.
    $purifier->config->set('HTML.SafeIframe', TRUE);
    $purifier->config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/|www\.google\.com/maps/(d/)?embed|.+\.vcccd\.edu|.+\.moorparkcollege\.edu|.+\.oxnardcollege\.edu|.+\.venturacollege\.edu)%');

    $purifier->config->set('HTML.MaxImgLength', NULL);
    $purifier->config->set('CSS.MaxImgLength', NULL);

    $def = $purifier->config->getHTMLDefinition(TRUE);
    $def->addAttribute('a', 'data-chrome', 'Text');
    $def->addAttribute('a', 'data-link-color', 'Text');
    $def->addAttribute('a', 'data-tweet-limit', 'Number');
    $def->addAttribute('iframe', 'align', 'Text');

    $html = $purifier->purify($html);

    // Replace some classes to bootstrap.
    $doc = new \DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    // Add bootstrap class for the tables.
    $tables = $doc->getElementsByTagName('table');
    foreach ($tables as $table) {
      $table->setAttribute('class', 'table ' . $table->getAttribute('class'));
    }

    // A special case: remove some inline style properties.
    $spans = $doc->getElementsByTagName('span');
    foreach ($spans as $span) {
      $style = $span->getAttribute('style');
      $class = $span->getAttribute('class');

      // Remove font-size.
      $style = preg_replace('/font-size:(\s)?([\d|.]+)(pt|em|px|%);/', '', $style);
      // Remove color.
      $style = preg_replace('/color:(\s)?#([\d|\w]+);/', '', $style);
      $span->setAttribute('style', $style);

      switch ($class) {
        case 'fa fa-clock-o fa-lg';
          $span->setAttribute('class', 'far fa-clock');
          break;

        case 'fa fa-mobile fa-2x';
        case 'fa fa-mobile  fa-2x';
          $span->setAttribute('class', 'fas fa-mobile-alt');
          break;

      }
    }

    $html = $doc->saveHTML();

    $html = $this->removeHtmlAndBodyTags($html);

    // Add back twitter widgets. Because scripts were removed.
    //    $hasTwitterTimeline = strpos($html, 'twitter-timeline');
    //    if ($hasTwitterTimeline !== FALSE) {
    //      $html .= '<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';
    //    }

    return $html;
  }

  /**
   * Explode element it and return widget ID.
   *
   * @param $value
   *
   * @return mixed
   */
  public function getWidgetId($value) {
    $value = explode('-', $value);

    return $value[count($value) - 1];
  }

  /**
   * Transform unstructured Contact block into structured data.
   *
   * @param $html
   * @param $title
   * @param $migratedData
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function html2ContactData($html, $title, $migratedData) {
    // Describe all fields.
    $contactData = [
      'title' => $title,
      'field_department' => '',
      'field_block_description' => '',
      'field_block_header' => '',
      'field_geolocation' => '',
      'field_address' => '',
      'field_address_altered' => '',
      'field_telephone' => '',
      'field_fax_number' => '',
      'field_hearing_impaired' => '',
      'field_emergency_phone_number' => '',
      'field_hours' => '',
      'field_business_hours' => '',
      'field_other_addresses_and_info' => '',
      'field_email' => '',
      'field_customer_assistance' => '',
      'field_note' => '',
      'field_social_media' => '',
    ];

    $contactRaw = str_replace('<h4', '|<h4', $html);
    $contactRaw = trim($contactRaw);
    $contactArr = explode('|', $contactRaw);
    $contactArr = array_values(array_filter(array_map('trim', $contactArr), 'strlen'));
    foreach ($contactArr as $item) {
      $cr = new Crawler($item);

      // Parse header/label.
      $header = trim($cr->filter('.contact-heading')->text());
      $header = rtrim($header, ':');

      // Parse strings.
      $strings = [];
      if ($cr->filter('.contact-name')->count()) {
        $nodeValues = $cr->filter('.contact-name')
          ->each(function (Crawler $node, $i) {
            return '<strong>' . trim($node->text()) . '</strong>';
          });
        $strings[] = implode('<br />', $nodeValues);
      }
      if ($cr->filter('.contact-info')->count()) {
        $nodeValues = $cr->filter('.contact-info')
          ->each(function (Crawler $node, $i) {
            return trim($node->html());
          });
        $strings[] = implode('<br />', $nodeValues);
      }
      if ($cr->filter('h6, p')->count()) {
        $nodeValues = $cr->filter('h6, p')
          ->each(function (Crawler $node, $i) {
            $output = '';
            foreach ($node as $DOMElement) {
              switch ($DOMElement->nodeName) {
                case 'h6':
                  $output .= '<h6>' . $this->innerHTML($DOMElement) . '</h6>';
                  break;
                default:
                  $output .= $this->innerHTML($DOMElement);
                  break;
              }
            }
            return trim($output);
          });
        $strings[] = implode('<br />', $nodeValues);
      }

      if (!empty($strings)) {
        $strings = implode('<br />', $strings);
      }
      else {
        $strings = '';
      }

      switch ($header) {
        case 'Contact':
        case 'Contact Info':
        case 'Division Contacts':
        case 'Risk Management':
        case 'Public Defender Screener':
        case 'Hours of Operation':
          $contactData['field_block_header'] = $header;
          $contactData['field_block_description'] = $strings;
          $contactData['field_address'] = $strings;

          break;

        case 'Telephone':
          $contactData['field_telephone'] = $strings;
          break;

        case 'Fax Number':
          $contactData['field_fax_number'] = $strings;
          break;

        case 'Emergency Phone Number':
          $contactData['field_emergency_phone_number'] = $strings;
          break;

        case 'Hours':
          $contactData['field_hours'] = str_replace('<br />', "\n", $strings);
          break;

        case 'Business Hours':
          $contactData['field_business_hours'] = str_replace('<br />', "\n", $strings);
          break;

        case 'Email':
          $contactData['field_email'] = strip_tags($strings);
          break;

        case 'Customer Assistance':
          $contactData['field_customer_assistance'] = $strings;
          break;

        case 'Hearing Impaired':
          $contactData['field_hearing_impaired'] = $strings;
          break;

        case 'Note':
          $contactData['field_note'] = str_replace('<br />', "\n", $strings);
          break;

        case 'Social Media':
          $contactData['field_social_media'] = $strings;
          break;

        default:
          // Store the rest data in "field_other_addresses_and_info".
          // Create links for mails and links.
          // The text processing filters service.
          $manager = \Drupal::service('plugin.manager.filter');
          // Getting filter plugin collection.
          $filter_collection = new FilterPluginCollection($manager, []);
          // Getting the filter_url plugin.
          $filter = $filter_collection->get('filter_url');
          // Setting the filter_url plugin configuration.
          $filter->setConfiguration([
            'settings' => [
              'filter_url_length' => 496,
            ],
          ]);
          $strings = _filter_url($strings, $filter);

          // Create the links for phone.
          $strings = preg_replace('/\d{3}-\d{3}-\d{4}/', '<a href="tel:$0">$0</a>', $strings);

          $contactData['field_other_addresses_and_info'] .= '<div class="field field--label-above field__items">'
            . '<div class="field__label">' . $header . '</div>'
            . '<div class="field__item">' . $strings . '</div>'
            . '</div>';
          break;
      }
    }

    $cr = new Crawler($html);

    // Extract Customer Assistance.
    if ($cr->filter('a.ca_url')->count()) {
      $nodeValues = $cr->filter('a.ca_url')
        ->each(function (Crawler $node, $i) {
          $uri = trim($node->extract(['href'])[0]);

          if (UrlHelper::isValid($uri, TRUE)) {
            return [
              'uri' => $uri,
              'title' => trim($node->text()),
            ];
          }

          return NULL;

        });
      if ($nodeValues !== NULL) {
        $contactData['field_customer_assistance'] = $nodeValues;
      }
    }

    // Extract Social Media.
    if ($cr->filter('a.fa')->count()) {
      $nodeValues = $cr->filter('a.fa')
        ->each(function (Crawler $node, $i) {
          $attr = $node->extract(['href', 'class'])[0];

          if (UrlHelper::isValid($attr[0], TRUE)) {
            $class = explode(' ', $attr[1])[1];
            $class = str_replace('fa-', '', $class);
            $class = str_replace('-square', '', $class);
            $class = trim($class);

            $iconSettings = [
              'masking' => [
                'mask' => '',
                'style' => 'fab',
              ],
              'power_transforms' => [
                'scale' => [
                  'type' => '',
                  'value' => '',
                ],
                'position_y' => [
                  'type' => '',
                  'value' => '',
                ],
                'position_x' => [
                  'type' => '',
                  'value' => '',
                ],
              ],
            ];

            return [
              'field_font_awesome_icon' => [
                'icon_name' => $class,
                'style' => 'fab',
                'settings' => serialize($iconSettings),
              ],
              'field_link' => [
                'uri' => $attr[0],
                'title' => trim($node->text()),
              ],
            ];
          }

        });

      $contactData['field_social_media'] = $nodeValues;
    }

    if ($contactData['field_address'] !== '') {
      $contactData['field_address'] = str_replace('<br />', ' ', $contactData['field_address']);
      $contactData['field_address'] = str_replace("\n", ' ', $contactData['field_address']);
      // Convert address to Address module fields.
      $geoData = $this->geoCode($contactData['field_address']);

      if ($geoData) {
        $contactData['field_geolocation'] = $geoData['location'];
        $contactData['field_address_altered'] = $this->string2Address($geoData);
      }
    }

    // Replace some valued by hardcoded data.
    // $this->locationCrutches($contactData, $migratedData);

    return $contactData;
  }

  /**
   * Create location.
   *
   * @param $data
   * @param $migratedData
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\node\Entity\Node
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createLocationNode($data, $migratedData) {
    // Don't allow create duplicates.
    // Just return exists node if exists.
    $query = \Drupal::entityTypeManager()->getStorage('paragraph')->getQuery();
    $query->condition('type', 'migrated_data')
      ->condition('field_original_id', $migratedData->get('field_original_id')
        ->getString())
      ->sort('id', 'ASC');
    $paragraphs = $query->execute();
    if (!empty($paragraphs)) {
      foreach ($paragraphs as $pid) {
        $node = Paragraph::load($pid)->getParentEntity();
        if ($node) {
          return $node;
        }
      }
    }

    // Prepare title.
    $title = '';
    if ($data['title'] != '') {
      $title .= trim(strip_tags($data['title']));
    }
    if ($data['field_block_description'] != '') {
      $description = str_replace('<br />', ', ', $data['field_block_description']);
      $description = strip_tags($description);
      $description = trim($description);
      $title .= ' | ' . $description;
    }
    if ($data['field_email'] != '') {
      $title .= ' | ' . trim(strip_tags($data['field_email']));
    }
    if ($data['field_telephone'] != '') {
      $title .= ' | ' . trim(strip_tags($data['field_telephone']));
    }
    if ($data['field_fax_number'] != '') {
      $title .= ' | ' . trim(strip_tags($data['field_fax_number']));
    }
    if ($data['field_emergency_phone_number'] != '') {
      $title .= ' | ' . trim(strip_tags($data['field_emergency_phone_number']));
    }

    $title = str_replace('<br />', ' ', $title);
    $title = str_replace("\n", ' ', $title);
    $title = Unicode::truncate($title, 255);

    $node = Node::create([
      'type' => 'location',
      'title' => $title,
      'moderation_state' => 'published',
    ]);

    if (isset($data['field_department']) && $data['field_department'] !== '') {
      $term = $this->getTermByName($data['field_department'], 'department');
      if ($term instanceof Term) {
        $node->set('field_department', $term);
      }
    }

    $description = [
      'value' => $data['field_block_description'],
      'format' => 'full_html',
    ];
    $node->set('field_block_description', $description);
    $node->set('field_block_header', $data['field_block_header']);
    $node->set('field_geolocation', $data['field_geolocation']);
    $node->set('field_address', $data['field_address_altered']);
    $node->set('field_telephone', $data['field_telephone']);
    $node->set('field_fax_number', $data['field_fax_number']);
    $node->set('field_hearing_impaired', $data['field_hearing_impaired']);
    $node->set('field_emergency_phone_number', $data['field_emergency_phone_number']);
    $node->set('field_hours', $data['field_hours']);
    $node->set('field_business_hours', $data['field_business_hours']);
    $node->set('field_other_addresses_and_info', [
      'value' => $data['field_other_addresses_and_info'],
      'format' => 'full_html',
    ]);
    $node->set('field_email', $data['field_email']);
    $node->set('field_customer_assistance', $data['field_customer_assistance'][0]);
    $node->set('field_note', $data['field_note']);

    // Create social paragraph and attach to the node.
    if ($data['field_social_media'] != '') {
      $socialItems = [];
      foreach ($data['field_social_media'] as $item) {
        $socialItems[] = $this->createParagraph('social_media_item', $item);
      }
      $socialData['field_item'] = $socialItems;
      $social = $this->createParagraph('social_media', $socialData);
      $node->set('field_social_media', $social);
    }

    // Save the info about migration.
    $node->set('field_migrated_data', $migratedData);

    $node->save();

    return $node;
  }

  /**
   * Gets address data from Google.
   *
   * @param $address
   * @param bool $use_api_key
   *
   * @return array|bool
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function geoCode($address, $use_api_key = TRUE) {
    if (empty($address)) {
      return FALSE;
    }

    // Google has a limit for requests 10 items per sec.
    sleep(0.6);

    $request_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address;

    if ($use_api_key === TRUE) {
      if (!empty(\Drupal::config('geolocation.settings')
        ->get('google_map_api_server_key'))
      ) {
        $request_url .= '&key=' . \Drupal::config('geolocation.settings')
            ->get('google_map_api_server_key');
      }
      elseif (!empty(\Drupal::config('geolocation.settings')
        ->get('google_map_api_key'))
      ) {
        $request_url .= '&key=' . \Drupal::config('geolocation.settings')
            ->get('google_map_api_key');
      }
    }
    if (!empty(\Drupal::config('geolocation.settings')
      ->get('google_map_custom_url_parameters')['language'])
    ) {
      $request_url .= '&language=' . \Drupal::config('geolocation.settings')
          ->get('google_map_custom_url_parameters')['language'];
    }
    if (!empty(\Drupal::config('geolocation.settings')
      ->get('google_map_custom_url_parameters')['region'])
    ) {
      $request_url .= '&region=' . \Drupal::config('geolocation.settings')
          ->get('google_map_custom_url_parameters')['region'];
    }

    try {
      $result = Json::decode(\Drupal::httpClient()
        ->request('GET', $request_url)
        ->getBody());
    } catch (RequestException $e) {
      watchdog_exception('alex_tweaks', $e);
      return FALSE;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['results'][0]['geometry'])
    ) {
      return FALSE;
    }

    return [
      'location' => $result['results'][0]['geometry']['location'],
      'address_components' => $result['results'][0]['address_components'],
      'address' => empty($result['results'][0]['formatted_address']) ? '' : $result['results'][0]['formatted_address'],
    ];
  }

  /**Convert geo code data to address.
   *
   * @param $data
   *
   * @return array
   */
  public function string2Address($data) {
    $postalTown = '';
    $countryCode = NULL;
    $postalCode = NULL;
    $streetNumber = NULL;
    $neighborhood = NULL;
    $premise = NULL;
    $route = NULL;
    $locality = NULL;
    $administrativeArea = NULL;
    $political = NULL;

    $defaults = [
      'country_code' => '',
      'administrative_area' => '',
      'locality' => '',
      'dependent_locality' => '',
      'postal_code' => '',
      'sorting_code' => '',
      'address_line1' => '',
      'address_line2' => '',
      'organization' => '',
      'given_name' => '',
      'additional_name' => '',
      'family_name' => '',
    ];

    foreach ($data['address_components'] as $key => $value) {
      $component = $data['address_components'][$key];
      $types = $component['types'];

      switch ($types[0]) {
        case 'country':
          $countryCode = $component['short_name'];
          break;

        case 'postal_town':
          $postalTown = $component['long_name'];
          break;

        case 'postal_code':
          $postalCode = $component['long_name'];
          break;

        case 'street_number':
          $streetNumber = $component['long_name'];
          break;

        case 'neighborhood':
          $neighborhood = $component['long_name'];
          break;

        case 'premise':
          $premise = $component['long_name'];
          break;

        case 'political':
          $political = $component['long_name'];
          break;

        case 'route':
          $route = $component['short_name'];
          break;

        case 'locality':
          $locality = $component['long_name'];
          break;

        case 'administrative_area_level_1':
          $administrativeArea = $component['short_name'];
          break;

        case 'administrative_area_level_2':
          $administrativeArea2 = $component['short_name'];
          break;

      }
    }

    if ($streetNumber) {
      $defaults['address_line1'] = $streetNumber . ' ' . $route;
    }
    else {
      if ($route) {
        $defaults['address_line1'] = $route;
      }
      else {
        if ($premise) {
          $defaults['address_line1'] = $premise;
        }
      }
    }

    //    if (($locality && $postalTown) && ($locality !== $postalTown)) {
    //      $defaults['address_line2'] = $locality;
    //    }
    //    else {
    //      if (!$locality && $neighborhood) {
    //        $defaults['address_line2'] = $neighborhood;
    //      }
    //    }
    //

    //    if ($postalTown) {
    //      $defaults['locality'] = $postalTown;
    //    }

    //    if (!$locality && $political) {
    //      $defaults['locality'] = $political;
    //    }

    if ($locality) {
      $defaults['locality'] = $locality;
    }

    if ($countryCode) {
      $defaults['country_code'] = $countryCode;
    }

    if ($administrativeArea) {
      $defaults['administrative_area'] = $administrativeArea;
    }

    if ($postalCode) {
      $defaults['postal_code'] = $postalCode;
    }

    if ($premise) {
      $defaults['organization'] = $premise;
    }

    return $defaults;
  }

  /**
   * Create a redirect.
   *
   * @param $internalSourceUrl
   * @param \Drupal\node\NodeInterface $node
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createRedirectToNode($internalSourceUrl, NodeInterface $node) {
    // TODO: Check if exists via
    //  $redirects = \Drupal::service('redirect.repository')->findBySourcePath($remoteUrl);.
    Redirect::create([
      'redirect_source' => $internalSourceUrl,
      'redirect_redirect' => 'internal:/node/' . $node->id(),
      'language' => 'und',
      'status_code' => '301',
    ])->save();
  }

  /**
   * Create a redirect to the file.
   *
   * @param string $remoteUrl
   * @param \Drupal\file\FileInterface $file
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createRedirectForFile(string $remoteUrl, FileInterface $file) {
    //    $remoteUrl = str_replace('%20', ' ', $remoteUrl);
    $remoteUrl = urldecode($remoteUrl);
    $remoteUrl = parse_url($remoteUrl);
    $remoteUrl = ltrim($remoteUrl['path'], '/');

    $redirects = \Drupal::service('redirect.repository')
      ->findBySourcePath($remoteUrl);

    if (empty($redirects)) {

      $internalFileUri = 'internal:' . file_url_transform_relative(file_create_url($file->getFileUri()));
      //      $internalFileUri = str_replace('%20', ' ', $internalFileUri);
      $internalFileUri = urldecode($internalFileUri);

      Redirect::create([
        'redirect_source' => $remoteUrl,
        'redirect_redirect' => $internalFileUri,
        'language' => 'und',
        'status_code' => '301',
      ])->save();

    }
  }

  /**
   * Transform the paths with image styles to real path.
   *
   * @param $path
   *
   * @return string|string[]|null
   *
   * @example
   * Replace from
   *   /sites/default/files/styles/faculty_photo/public/images/headshot/Kling.jpg
   *   to /sites/default/files/images/headshot/Kling.jpg
   */
  public function extractRealImagePath($path) {
    $pattern = '/\/sites\/default\/files\/styles\/(.*)\/public\/images\/(.*)\/(.*)/';
    $replacement = '/sites/default/files/images/$2/$3';

    return preg_replace($pattern, $replacement, $path);
  }

  /**
   * Inline content has Stage domain in the text.
   *
   * @param $url
   *
   * @return mixed
   */
  public function fixDomain($url) {
    return str_replace('vcccd-stg.vcccd.edu', 'vcccd.edu', $url);
  }

  /**
   * Extract categories from file path.
   *
   * @param $filePath
   *
   * @return array
   */
  public function extractCategoriesFromFlePath($filePath) {
    $filename = basename($filePath);
    $filePath = parse_url($filePath);
    $filePath = $filePath['path'];

    $filePath = $this->extractRealImagePath($filePath);

    $imageTags = str_replace('/sites/default/files/images/', '', $filePath);
    $imageTags = str_replace('/sites/default/files/', '', $imageTags);

    $imageTags = str_replace($filename, '', $imageTags);
    $imageTags = trim($imageTags, '/');
    //    $imageTags = str_replace([
    //      '&amp;',
    //      '%20',
    //      '_',
    //    ], [
    //      '&',
    //      ' ',
    //      ' ',
    //    ], $imageTags);
    $imageTags = urldecode($imageTags);
    $imageTags = explode('/', $imageTags);
    $imageTags = array_map('trim', $imageTags);

    return $imageTags;
  }

  /**
   * Helper for removing HTML and BODY tags.
   *
   * @param $html
   *
   * @return mixed|string
   */
  public function removeHtmlAndBodyTags($html) {
    $html = str_replace('<!DOCTYPE html PUBLIC " -//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">', '', $html);
    $html = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">', '', $html);
    $html = str_replace('<html><body>', '', $html);
    $html = str_replace('</body></html>', '', $html);

    // Remove 'ZERO WIDTH SPACE' symbol.
    $html = str_replace('&#8203;', ' ', $html);
    $html = trim($html);

    return $html;
  }

  /**
   * Remove unwanted HTML comments.
   *
   * @param string $html
   *
   * @return string|string[]|null
   */
  public function removeHtmlComments($html = '') {
    return preg_replace('/<!--(.|\s)*?-->/', '', $html);
  }

  /**
   * Replace the links to the new structure of the site.
   *
   * @param $html
   * @param $domain
   *
   * @return mixed|string
   */
  public function replaceLinksToNewStructure($html, $domain = 'www.vcccd.edu') {
    $domain = rtrim($domain, '/'); // Removed slash.

    $doc = new \DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $links = $doc->getElementsByTagName('a');

    foreach ($links as $link) {
      $url = $link->getAttribute('href');
      // Make the link absolute.
      if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $url = $domain . $url;
      }
      // Don't replace the URL's for migrated attachments.
      $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
      if ($this->isInternalLink($url) && $ext == '') {
        $url = parse_url($url);
        if (isset($url['host'])) {
          $currentSiteDomainInfo = $this->getCurrentDomain();
          $currentSiteDomainInfo = parse_url($currentSiteDomainInfo);
          $currentSiteDomainHost = $currentSiteDomainInfo['host'];

          if ($url['host'] == $currentSiteDomainHost) {
            // Make sure, we have only one slash on the left.
            $newUrl = ltrim($url['path'], '/');
            $newUrl = '/' . $newUrl;
            $link->setAttribute('href', $newUrl);
          }
        }
      }
    }

    $html = $doc->saveHTML();
    $html = $this->removeHtmlAndBodyTags($html);

    return $html;
  }

  /**
   * Replace the links to the new structure of the site in HTML options.
   *
   * @param $html
   * @param string $domain
   *
   * @return mixed|string
   * @deprecated we can remove it.
   *
   */
  public function replaceLinksInOptionToNewStructure($html, $domain = 'www.vcccd.edu') {
    $domain = rtrim($domain, '/'); // Removed slash.

    $doc = new \DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $links = $doc->getElementsByTagName('option');

    foreach ($links as $link) {
      $url = $link->getAttribute('value');

      // Make the link absolute.
      if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $url = $domain . $url;
      }
      // Don't replace the URL's for migrated attachments.
      $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
      if ($this->isInternalLink($url) && $ext == '') {
        $url = parse_url($url);
        if (isset($url['host'])) {
          $currentSiteDomainInfo = $this->getCurrentDomain();
          $currentSiteDomainInfo = parse_url($currentSiteDomainInfo);
          $currentSiteDomainHost = $currentSiteDomainInfo['host'];

          if ($url['host'] == $currentSiteDomainHost) {
            // Make sure, we have only one slash on the left.
            $newUrl = ltrim($url['path'], '/');
            $newUrl = '/' . $newUrl;
            $link->setAttribute('value', $newUrl);
          }
        }
      }
    }

    $html = $doc->saveHTML();
    $html = $this->removeHtmlAndBodyTags($html);

    return $html;
  }

  /**
   * Checks if the URL is internal.
   *
   * @param $url
   *
   * @return bool
   */
  public function isInternalLink($url) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
      $url = parse_url($url);
      if (isset($url['host'])) {
        $currentSiteDomainInfo = $this->getCurrentDomain();
        $currentSiteDomainInfo = parse_url($currentSiteDomainInfo);
        $currentSiteDomainHost = $currentSiteDomainInfo['host'];

        if ($url['host'] == $currentSiteDomainHost) {
          return TRUE;
        }
      }
    }
    else {
      // Assuming, relative URL's are internal.
      return TRUE;
    }

    return FALSE;
  }


  /**
   * Some contact data migrates as wrong.
   * We can't migrate totally free text to the fields.
   * Just create an array and update the location data.
   * No other ways.
   *
   * @param $data
   * @param $migratedData
   */
  public function locationCrutches(&$data, $migratedData) {
    $locationOriginalID = $migratedData->get('field_original_id')->getString();
    $correctData = [
      116537 => [
        'field_address_altered' => '',
      ],
      101311 => [
        'field_address_altered' => '',
      ],
      106384 => [
        'field_address_altered' => '',
      ],
    ];

    if (isset($correctData[$locationOriginalID])) {
      $data = array_replace_recursive($data, $correctData[$locationOriginalID]);
    }

  }

  /**
   * Print inner HTML for DOMElement.
   *
   * @param \DOMElement $element
   *
   * @return string
   */
  public function innerHTML(\DOMElement $element) {
    $doc = $element->ownerDocument;

    $html = '';

    foreach ($element->childNodes as $node) {
      $html .= $doc->saveHTML($node);
    }

    return $html;
  }

  /**
   * Transform URL from Ruslan's local sandbox to the real value.
   *
   * @param $string
   *
   * @return mixed
   */
  public function replaceLocalDomainToReal($string) {
    $search = [
      'http://kwall-vcccd-d8.devel/',
      'https://kwall-vcccd-d8.devel/',
    ];

    return str_replace($search, 'https://www.vcccd.edu', $string);
  }

  /**
   * Convert the path to legacy URL.
   *
   * @param $string
   *
   * @return string
   */
  public function pathToLegacyURL($string) {
    return $this->getCurrentDomain() . $string;
  }

  /**
   * Helper for searching a Banner by legacy NID.
   *
   * @param $nid
   *
   * @return |null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findBannerByLegacyId($nid) {
    $params = [
      'type' => 'migrated_data',
      'field_original_id' => $nid,
    ];

    // Load the entities by the value of the field.
    $paragraphs = \Drupal::entityTypeManager()
      ->getStorage('paragraph')
      ->loadByProperties($params);

    if (!empty($paragraphs)) {
      $lastEl = array_values(array_slice($paragraphs, -1))[0];
      $media = $lastEl->getParentEntity();
      if ($media instanceof MediaInterface) {
        return $media;
      }
    }

    return NULL;
  }

  /**
   * Creates a custom block.
   *
   * @param bool $title
   * @param string $body
   * @param string $bundle
   * @param bool $save
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createBlockContent($title = FALSE, $body = '', $bundle = 'basic', $save = TRUE) {
    $title = $title ?: $this->randomMachineName();
    $block_content = BlockContent::create([
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en',
      'body' => [
        'value' => $body,
        'format' => 'restricted_html',
      ],
    ]);
    if ($block_content && $save === TRUE) {
      $block_content->save();
    }
    return $block_content;
  }

  /**
   * Try to convert the text to UTF-8.
   *
   * @param $string
   *
   * @return bool|false|string
   */
  public function convert2UTF8($string) {
    return iconv(mb_detect_encoding($string, mb_detect_order(), TRUE), "UTF-8", $string);
  }

  /**
   * Number of seconds passed through midnight.
   *
   * @param $value
   *
   * @return float|int|string|null
   */
  public function getTimestampFromTime($value) {

    $date = DateTimePlus::createFromFormat('g:i A', $value);
    $hour = $date->format('H');
    $minute = $date->format('i');
    $second = $date->format('s');

    $value = $hour * 60 * 60;
    $value += $minute * 60;
    $value += $second;

    return $value;
  }

  /**
   * Function to check string starting with given substring.
   *
   * @param $string
   * @param $startString
   *
   * @return bool
   */
  public function startsWith($string, $startString) {
    $len = strlen($startString);

    return (substr($string, 0, $len) === $startString);
  }

  /**
   * Add protocol to the links.
   *
   * @param $url
   *
   * @return string
   */
  public function addHttps($url) {
    if ($this->startsWith($url, '//')) {
      if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https:" . $url;
      }
    }

    return $url;
  }

  /**
   * Get migrated node by legacy node id.
   *
   * @param $nid
   *
   * @return |null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNodeByLegacyId($nid) {
    $params = [
      'type' => 'migrated_data',
      'field_original_id' => $nid,
    ];

    // Load the entities by the value of the field.
    $paragraphs = \Drupal::entityTypeManager()
      ->getStorage('paragraph')
      ->loadByProperties($params);

    if (!empty($paragraphs)) {
      $lastEl = array_values(array_slice($paragraphs, -1))[0];
      $node = $lastEl->getParentEntity();
      if ($node instanceof NodeInterface) {
        return $node;
      }
    }

    return NULL;
  }

}
