<?php

namespace Drupal\oregon_tech_xml_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;

/**
 * Searches Legacy Path from file.
 *
 * @MigrateProcessPlugin(
 *   id = "legacy_path"
 * )
 */

class LegacyPathPlugin extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {
    $path = NUll;
    $spreadsheet = DRUPAL_ROOT . '/modules/custom/oregon_tech_xml_migration/asserts/csv/StudentProfileUrls.csv';
    if (($handle = fopen($spreadsheet, "r")) !== FALSE) {
      while (($data = fgetcsv($handle)) !== FALSE) {
        if (strpos($data[0], '/' . $value) !== FALSE) {
          $path = $data[0];
          $html = file_get_contents($path);
          $dom = new \DOMDocument();
          @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
          if ($dom->nodeType == 13) {
            $xml = simplexml_import_dom($dom);
            $content = $xml->xpath('//div[contains(@class, "StudentProfile")]/div');
            if (!empty($content)) {
              foreach ($content as $item) {
                if (isset($item->strong) && $item->strong->__toString() == 'Major(s): ') {
                  $link = $item->a->attributes()->{'href'};
                  if (!empty($link)) {
                    $query = \Drupal::database()->select('node__field_legacy_path', 'p');
                    $query->condition('field_legacy_path_value', $link->__toString());
                    $query->addField('p', 'entity_id');
                    $nid = $query->execute()->fetchField();

                    if ($nid) {
                      $row->setDestinationProperty('field_program', [['target_id' => $nid]]);
                    }
                  }
                  break;
                }
              }
            }
          }
          break;
        }
      }
      fclose($handle);
    }

    $path = str_replace('https://www.oit.edu', '', $path);
    return $path;
  }

}
