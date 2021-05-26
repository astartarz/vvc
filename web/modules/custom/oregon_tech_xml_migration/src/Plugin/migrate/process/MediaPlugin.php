<?php

namespace Drupal\oregon_tech_xml_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\oregon_tech_layout_migration\MediaMigration;
/**
 * Creates media.
 *
 * @MigrateProcessPlugin(
 *   id = "media"
 * )
 */

class MediaPlugin extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {
    if (!empty($value)) {
      $mediaMigration = new MediaMigration();
      $media = $mediaMigration->createMedia('inline_media', $value);
      return ['target_id' => $media->id()];
    }
  }

}
