<?php

namespace Drupal\oregon_tech_xml_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Creates terms.
 *
 * @MigrateProcessPlugin(
 *   id = "term"
 * )
 */

class TermPlugin extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {
    switch ($destination_property) {
      case 'field_story_type':
        if ($value == 'Alumnus' || $value == 'Alumna') {
          $value = 'Alumni';
        }
        return strtolower($value);
        break;

      case 'field_story_category':
        $voc = 'story_category';
        break;

      case 'field_campus':
        $voc = 'campus';
        break;

      case 'field_article_taxonomies':
        $voc = 'article_category';
        break;
    }

    $tids = [];
    $items = [];
    if ($value instanceof \SimpleXMLElement) {
      foreach ($value->string as $item) {
        $items[] = $item->__toString();
      }
    }
    else {
      $items[] = $value;
    }
    foreach ($items as $name) {
      if (!empty($name)) {
        if ($voc == 'campus') {
          $name = str_replace('-', ' ', $name);
        }
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => $voc]);
        if (!empty($terms)) {
          $term = reset($terms);
        }
        else {
          if ($voc == 'campus') {
            return NULL;
          }
          $term = Term::create([
            'name' => ucfirst($name),
            'vid' => $voc,
          ]);
          $term->save();
        }
        $tids[] = ['target_id' => $term->id()];
      }
    }

    return $tids;
  }

}
