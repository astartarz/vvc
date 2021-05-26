<?php

namespace Drupal\oregon_tech_xml_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;

/**
 * Date format.
 *
 * @MigrateProcessPlugin(
 *   id = "date"
 * )
 */

class DatePlugin extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {
    if (!empty($value)) {
      $dateParts = explode('T', $value);
      return $dateParts[0];
    }
  }

}
