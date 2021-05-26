<?php

namespace Drupal\vvc_migration;

use Drupal\Component\Utility\Unicode;
use Drupal\media\MediaInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\redirect\Entity\Redirect;
use Drupal\taxonomy\Entity\Term;

/**
 * Create medias from url.
 */
class MediaMigration {

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
   * Describe all basic field for each media bundle.
   */
  public function setMediaFields() {
    $this->mediaFieldsMapper = [
      'file' => 'field_media_file',
      'image' => 'field_media_image',
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
      'file' => 'public://files',
      'image' => 'public://images',
    ];
  }

  /**
   * Get the file path of the a media by bundle.
   *
   * @param $bundle
   *
   * @return mixed
   */
  public function getMediaFilePathByBundle($bundle) {
    $this->setFilePath();

    return $this->mediaFilePathMapper[$bundle];
  }

  /**
   * Create or get media entity.
   *
   * @param $bundle
   * @param $absoluteUrl
   * @param array $attributes
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|\Drupal\media\Entity\Media|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createMedia($bundle, $absoluteUrl, $attributes = []) {
    $absoluteUrl = str_replace('www0', 'www', $absoluteUrl);
    $url = parse_url($absoluteUrl);
    if (empty($url['host'])) {
      $absoluteUrl = 'http://www.vvc.edu/' . ltrim($absoluteUrl, '/');
    }

    $media = NULL;
    $fileName = basename($absoluteUrl);
    $file = $this->findFileByName($fileName, $bundle, $absoluteUrl);
    if ($file instanceof FileInterface) {
      // Check if media already exists.
      $media = $this->findMediaByFid($bundle, $file->id());
      if (!$media instanceof MediaInterface) {
        // Create media.
        $mediaFieldName = $this->getMediaFiledNameByBundle($bundle);

        $replacement = [
          '""' => '',
          '%20' => ' ',
        ];

        $data = [
          'target_id' => $file->id(),
        ];

        $mediaName = $file->getFilename();

        $data['title'] = $mediaName;
        if (!empty($attributes['title'])) {
          $data['title'] = Unicode::truncate(str_replace(array_keys($replacement), array_values($replacement), $attributes['title']), 60);
        }

        $data['alt'] = $mediaName;
        if (!empty($attributes['alt'])) {
          $data['alt'] = Unicode::truncate(str_replace(array_keys($replacement), array_values($replacement), $attributes['alt']), 60);
        }

        if (!empty($attributes['description'])) {
          $data['description'] = Unicode::truncate(str_replace(array_keys($replacement), array_values($replacement), $attributes['description']), 255);
        }

        if (!empty($attributes['title'])) {
          $mediaName = $data['title'];
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
        $media->save();
      }
    }

    return $media;
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
  public function findFileByName($filename, $bundle, $remoteUrl) {

    $filename = str_replace('%20', ' ', $filename);
    $remoteUrl = str_replace(' ', '%20', $remoteUrl);

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['filename' => $filename]);

    $result = NULL;
    $sizes = [];
    $remoteSize = $this->curl_get_file_size($remoteUrl);
    if ($remoteSize != -1) {
      foreach ($files as $file) {
        $uri = $file->getFileUri();
        if (file_exists($uri)) {
          $fileSize = filesize($uri);
          if (!in_array($fileSize, $sizes)) {
            $sizes[] = $fileSize;
            if ($fileSize == $remoteSize) {
              $result = $file;
            }
          }
        }
        else {
          // remove duplicates.
          $id = $file->id();
          $file->delete();

          $medias = \Drupal::entityTypeManager()
            ->getStorage('media')
            ->loadByProperties([
              $this->getMediaFiledNameByBundle($bundle) => $id,
            ]);
          foreach ($medias as $media) {
            $media->delete();
          }
        }
      }

      if (empty($result)) {
        $destination = $this->getMediaFilePathByBundle($bundle);
        $remoteUrl = str_replace(' ', '%20', $remoteUrl);

        if (\Drupal::service('file_system')
          ->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
          set_time_limit(0);
          $fileContents = @file_get_contents($remoteUrl);
          if (!empty($fileContents)) {
            $newDestination = $destination . '/' . $filename;
            if ($file = file_save_data($fileContents, $newDestination)) {
              $this->createRedirectForFile($remoteUrl, $file);
              $result = $file;
            }
          }
        }
      }

      if (!$this->checkRedirectForFile($remoteUrl) && !empty($file)) {
        $this->createRedirectForFile($remoteUrl, $file);
      }
    }

    return $result;
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
  public function findMediaByFid($bundle, $fid) {
    $mediaFieldName = $this->getMediaFiledNameByBundle($bundle);

    $params = [
      'bundle' => $bundle,
      'status' => 1,
    ];

    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('media', $bundle);

    // Check if the field exists in the $entity_type.
    if (array_key_exists($mediaFieldName, $field_definitions)) {
      // And add the value of the field as a parameter.
      $params[$mediaFieldName] = $fid;

      // Load the entities by the value of the field.
      $medias = \Drupal::entityTypeManager()
        ->getStorage('media')
        ->loadByProperties($params);

      if (!empty($medias)) {
        return array_pop($medias);
      }

    }

    return [];
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

          $doc = new \DOMDocument();
          @$doc->loadHTML(mb_convert_encoding($img_tag, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
            $filePath = str_replace('"', '', $attributes['src']);
            $filePath = str_replace('&amp;', '&', $filePath);

            if ($this->isInternalLink($filePath)) {
              $media = $this->createMedia('image', $filePath, $attributes);
              if ($media instanceof MediaInterface) {
                $replace = '<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="' . $media->uuid() . '" data-view-mode="default"></drupal-media>';
                $html = str_replace($img_tag, $replace, $html);
              }
            }
          }
        }
      }
    }

    // Find all Assets (Images, PDF and etc) in the links.
    $doc = new \DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $links = $doc->getElementsByTagName('a');
    foreach ($links as $link) {

      $currentUrl = trim($link->getAttribute('href'));
      $currentUrl = str_replace('"', '', $currentUrl);
      $currentUrl = str_replace('&amp;', '&', $currentUrl);
      $currentFilename = basename($currentUrl);
      $ext = strtolower(pathinfo($currentFilename, PATHINFO_EXTENSION));

      if ($this->isInternalLink($currentUrl)) {
        $attributes = [
          'alt' => $link->getAttribute('title'),
          'title' => $currentFilename,
          'description' => $link->getAttribute('title'),
        ];

        $types = [
          'image' => 'field_media_image',
          'file' => 'field_media_file',
        ];
        foreach ($types as $type => $field) {
          switch ($type) {
            case 'image':
              $extensions = 'png, gif, jpg, jpeg, jpgp';
              break;

            case 'file':
              $extensions = 'txt, rtf, doc, docx, ppt, pptx, xls, xlsx, pdf, odf, odg, odp, ods, odt, fodt, fods, fodp, fodg, key, numbers, pages';
              break;
          }

          $extensionMapper = explode(', ', $extensions);
          if (in_array($ext, $extensionMapper)) {
            $media = $this->createMedia($type, $currentUrl, $attributes);
            if ($media instanceof MediaInterface) {
              // Get real path to the file.
              $fid = $media->get($field)
                ->getValue()[0]['target_id'];
              $file = File::load($fid);
              $newPath = file_url_transform_relative(file_create_url($file->getFileUri()));
              // Replace link to the file.
              if ($newPath !== '') {
                $link->setAttribute('href', $newPath);
              }
            }
            break;
          }
        }
      }
    }

    return $doc->saveHTML();
  }

  /**
   * Checks if the URL is internal.
   *
   * @param $url
   *
   * @return bool
   */
  public function isInternalLink($url) {
    $url = str_replace('www0', 'www', $url);
    if (filter_var($url, FILTER_VALIDATE_URL)) {
      $url = parse_url($url);
      if (empty($url['host']) || (!empty($url['host']) && ($url['host'] == 'www.vvc.edu'))) {
        return TRUE;
      }
    }
    else {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns the size of a file without downloading it, or -1 if the file
   * size could not be determined.
   *
   * @param $url - The location of the remote file to download. Cannot
   * be null or empty.
   *
   * @return The size of the file referenced by $url, or -1 if the size
   * could not be determined.
   */
  public function curl_get_file_size( $url ) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    $data = curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

    curl_close($ch);
    return $size;
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
    $remoteUrl = parse_url($remoteUrl);
    $remoteUrl = ltrim($remoteUrl['path'], '/');

    $redirects = \Drupal::service('redirect.repository')
      ->findBySourcePath($remoteUrl);

    if (empty($redirects)) {

      $internalFileUri = 'internal:' . file_url_transform_relative(file_create_url($file->getFileUri()));
      $internalFileUri = str_replace('%20', ' ', $internalFileUri);

      Redirect::create([
        'redirect_source' => $remoteUrl,
        'redirect_redirect' => $internalFileUri,
        'language' => 'und',
        'status_code' => '301',
      ])->save();

    }
  }

  /**
   * Checks an old redirect to the file.
   *
   * @param string $remoteUrl
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function checkRedirectForFile(string $remoteUrl) {
    $status = FALSE;
    $remoteUrl = parse_url($remoteUrl);
    $remoteUrl = ltrim($remoteUrl['path'], '/');

    $redirects = \Drupal::service('redirect.repository')
      ->findBySourcePath($remoteUrl);
    if (!empty($redirects)) {
      $redirect = reset($redirects);
      $redirectUrl = $redirect->get('redirect_redirect')->uri;
      if (!file_exists($redirectUrl)) {
        $redirect->delete();
      }
      else {
        $status = TRUE;
      }
    }

    return $status;
  }

}
