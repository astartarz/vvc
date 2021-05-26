<?php

namespace Drupal\oregon_tech_xml_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;

/**
 * Creates links.
 *
 * @MigrateProcessPlugin(
 *   id = "title"
 * )
 */

class TItlePlugin extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {
    $value = array_map('trim', $value);
    $title = implode(' ', $value);
    return $title;
  }

}
