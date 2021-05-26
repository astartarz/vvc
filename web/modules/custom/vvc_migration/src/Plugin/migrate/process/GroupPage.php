<?php

namespace Drupal\vvc_migration\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\user\Entity\User;
use Drupal\vvc_migration\MediaMigration;

/**
 * Parse data from url and update all fields.
 *
 * @MigrateProcessPlugin(
 *   id = "group_page"
 * )
 */

class GroupPage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {

    if (!empty($value)) {
      $source = $row->getSource();
      $label = ucfirst(trim($source["group"]));
      $row->setDestinationProperty('field_legacy_group', $label);

      if (!empty($label)) {
        $groups = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['label' => $label]);
        if (empty($groups)) {
          $group = Group::create([
            'type' => 'site_group',
            'label' => $label
          ]);
          $group->setOwner(User::load(1));
          $group->enforceIsNew();
          $group->save();
        }
      }

      $urlParts = parse_url($value);
      $relativeUrl = rtrim($urlParts['path'], '/');
      $row->setDestinationProperty('field_legacy_path', $relativeUrl);

      $exists = FALSE;
      $migrationUrl = Database::getConnection()->select('node__field_legacy_path', 'mu')
        ->fields('mu', ['entity_id'])
        ->condition('field_legacy_path_value', $relativeUrl)
        ->execute()
        ->fetchField();
      if ($migrationUrl) {
        $value = 'internal:/node/' . $migrationUrl;
        $exists = TRUE;
      }

      if (!$exists) {
        $html = file_get_contents($value);
        $html = str_replace('www0', 'www', $html);
        $html = str_replace('"http://www.vvc.edu', '"', $html);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        if ($dom->nodeType == 13) {
          $xml = simplexml_import_dom($dom);

          $titleNodes = $xml->xpath('//h1');
          if (!empty($titleNodes)) {
            foreach ($titleNodes as $titleNode) {
              $titleHtml = $titleNode->asXML();
              $title = trim(html_entity_decode(strip_tags($titleHtml)), " \t\n\r\0\x0B\xc2\xa0");
              if (!empty($title)) {
                break;
              }
            }
          }
          if (empty($title)) {
            $titleNode = $xml->xpath('//title');
            if (!empty($titleNode)) {
              $titleHtml = $titleNode[0]->asXML();
              $title = trim(html_entity_decode(trim(strip_tags($titleHtml))));
            }
          }
          $row->setDestinationProperty('title', $title);

          $linkNode = $xml->xpath('//a[@id="de-link"]');
          if (!empty($linkNode)) {
            $linkHtml = $linkNode[0]->asXML();
          }

          $content = $xml->xpath('//div[@id="main"]');
          if (empty($content)) {
            $content = $xml->xpath('//div[@class="section"]');
          }
          if (!empty($content)) {
            $html = $content[0]->asXML();
            if (!empty($titleHtml)) {
              $html = str_replace($titleHtml, '', $html);
            }
            if (!empty($linkHtml)) {
              $html = str_replace($linkHtml, '', $html);
            }
            $html = html_entity_decode($html);
            $html = $html = preg_replace('/>(\s)+</m', '><', $html);
            $html = str_replace('<!-- com.omniupdate.div label="content" button="770" group="Everyone" padding="3" border="#e5e5e5" bgcolor="white" --><!-- ouc:editor csspath="/css/2col-editor.css" cssmenu="/css/2col-styles.txt" width="760"/ -->', '', $html);
            $html = preg_replace('/<p><a href="javascript:window\.print\(\)"><img[^>]*><\/a><\/p>/', '', $html);
            $mediaMigration = new MediaMigration();
            $html = $mediaMigration->assets2Media($html);
            return [
              'value' => $html,
              'format' => 'full_html'
            ];
          }
        }
      }
    }

    if (empty($title)) {
      throw new MigrateSkipRowException();
    }
  }

}
