<?php

namespace Drupal\oregon_tech_xml_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\oregon_tech_layout_migration\MediaMigration;

/**
 * Update html.
 *
 * @MigrateProcessPlugin(
 *   id = "textarea"
 * )
 */

class TextareaPlugin extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {
    if (!is_array($value)) {
      $value = [$value];
    }
    if (isset($value[1])) {
      $value[1] = strip_tags($value[1]);
    }
    $body = ['format' => 'full_html'];
    $keys = ['value', 'summary'];
    $media = new MediaMigration();
    foreach ($value as $key => $item) {
      $html = $media->assets2Media($item);
      if ($key == 1) {
        $html = strip_tags($html);
      }
      $body[$keys[$key]] = $html;
    }
    return $body;
  }

}
