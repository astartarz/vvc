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
 *   id = "page"
 * )
 */

class Page extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, \Drupal\migrate\MigrateExecutableInterface $migrate_executable, \Drupal\migrate\Row $row, $destination_property) {

    if (!empty($value)) {
      $source = $row->getSource();

      $groups = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['label' => $source["department"]]);
      if (!empty($groups)) {
        $group = reset($groups);
      }
      else {
        $group = Group::create([
          'type' => 'site_group',
          'label' => $source["department"]
        ]);
        $group->setOwner(User::load(1));
        $group->enforceIsNew();
        $group->save();
      }

      $internal = FALSE;
      $mediaMigration = new MediaMigration();

      $url = parse_url($value);
      if (!empty($url['host'])) {
        if ($url['host'] == 'www.vvc.edu') {
          $internal = TRUE;
        }
      }
      else {
        $value = 'http://www.vvc.edu' . $value;
        $internal = TRUE;
      }

      $is_file = pathinfo($value, PATHINFO_EXTENSION);
      if ($internal && strlen($is_file) > 0 && $is_file != 'shtml') {
        $imageExt = ['png', 'gif', 'jpg', 'jpeg', 'jpgp'];
        if (array_search($is_file, $imageExt) !== FALSE) {
          $type = 'image';
          $field = 'field_media_image';
        }
        else {
          $type = 'file';
          $field = 'field_media_file';
        }
        $media = $mediaMigration->createMedia($type, $value);
        if (!empty($media)) {
          $fid = $media->get($field)->target_id;
          $file = File::load($fid);
          $uri = $file->getFileUri();
          $path = file_create_url($uri);
          $pathParse = parse_url($path);
          $value = 'internal:' . $pathParse["path"];
        }
        $internal = FALSE;
      }

      $urlParts = parse_url($value);
      $relativeUrl = rtrim($urlParts['path'], '/');
      $row->setDestinationProperty('field_legacy_path', $relativeUrl);

      $migrationUrl = Database::getConnection()->select('node__field_legacy_path', 'mu')
        ->fields('mu', ['entity_id'])
        ->condition('field_legacy_path_value', $relativeUrl)
        ->execute()
        ->fetchField();
      if ($migrationUrl) {
        $value = 'internal:/node/' . $migrationUrl;
        $internal = FALSE;
      }

      if ($value != '#' && empty($migrationUrl) && $internal) {
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
            $html = str_replace($titleHtml, '', $html);
            $html = str_replace($linkHtml, '', $html);
            $html = html_entity_decode($html);
            $html = $html = preg_replace('/>(\s)+</m', '><', $html);
            $html = str_replace('<!-- com.omniupdate.div label="content" button="770" group="Everyone" padding="3" border="#e5e5e5" bgcolor="white" --><!-- ouc:editor csspath="/css/2col-editor.css" cssmenu="/css/2col-styles.txt" width="760"/ -->', '', $html);
            $html = preg_replace('/<p><a href="javascript:window\.print\(\)"><img[^>]*><\/a><\/p>/', '', $html);
            $html = $mediaMigration->assets2Media($html);
            return [
              'value' => $html,
              'format' => 'full_html'
            ];
          }
        }
      }
      else {
        $menuName = $source["title"];
        $exists = FALSE;

        $menus = $group->getContent('group_content_menu:site_group_menu');
        if (!empty($menus)) {
          $menu = reset($menus);
          $menu_name = GroupContentMenuInterface::MENU_PREFIX . $menu->get('entity_id')->target_id;
          $tree = \Drupal::service('menu.link_tree')->load($menu_name, new MenuTreeParameters());
          foreach ($tree as $id => $item) {
            if ($item->link->getTitle() == $menuName) {
              $exists = TRUE;
            }
          }
        }
        if (!$exists) {
          if ($value == '#') {
            $value = 'route:<nolink>';
          }
          $menu_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->create([
            'title' => $menuName,
            'link' => ['uri' => $value],
            'menu_name' => $menu_name,
          ]);
          $menu_link->save();
        }

        throw new MigrateSkipRowException();
      }
    }
  }

}
