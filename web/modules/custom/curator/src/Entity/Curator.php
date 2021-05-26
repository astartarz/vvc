<?php
/**
 * Curator - Social post sync
 *
 * @package     curator
 * @author      Kwall <info@kwallcompany.com>
 * @license     GPL-2.0+
 * @link        http://www.kwallcompany.com/
 * @copyright   KwallCompany
 * Date:        06/26/2020
 * Time:        11:40 PM
 */

namespace Drupal\curator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\curator\CuratorInterface;

/**
 * Defines the curator entity.
 *
 * @ConfigEntityType(
 *   id = "curator",
 *   label = @Translation("Curator"),
 *   handlers = {
 *     "list_builder" = "Drupal\curator\Controller\CuratorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\curator\Form\CuratorForm",
 *       "edit" = "Drupal\curator\Form\CuratorForm",
 *       "delete" = "Drupal\curator\Form\CuratorDeleteForm",
 *     }
 *   },
 *   config_prefix = "curator",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "enable" = "enable",
 *     "limit" = "limit",
 *     "content_type" = "content_type",
 *     "feed" = "feed"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/curator/{curator}",
 *     "delete-form" = "/admin/config/system/curator/{curator}/delete",
 *   }
 * )
 */
class Curator extends ConfigEntityBase implements CuratorInterface {

  /**
   * The Curator ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Curator label.
   *
   * @var string
   */
  public $label;

  /**
   * Is enable.
   *
   * @var $enable
   */
  public $enable = FALSE;

  /**
   * Limit.
   *
   * @var $limit
   */
  public $limit = 20;

  /**
   * Curator feed id
   *
   * @var $feed
   */
  public $feed;

  /**
   * Enable content type.
   *
   * @var $content_type
   */
  public $content_type;

  /**
   * Settings array.
   *
   * @var $settings
   */
  public $settings;

  /**
   * {@inheritdoc}
   *
   * @return string
   */
  public function getFeedId() {
    return $this->feed;
  }

  /**
   * {@inheritdoc}
   *
   * @return bool
   */
  public function isEnabled() {
    return $this->enable;
  }

  /**
   * {@inheritdoc}
   *
   * @return int
   */
  public function limit() {
    return $this->limit;
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   */
  public function getContentType() {
    return $this->content_type;
  }

  /**
   * Mapping list of feed keys.
   *
   * @return string[]
   */
  public function getMappingList() {
    return [
      'network_name' => 'text',
      'source_identifier' => 'text',
      'text' => 'text',
      'image' => 'entity_reference',
      'user_screen_name' => 'text',
      'user_full_name' => 'text',
      'user_url' => 'link',
      'url' => 'link',
      'source_created_at' => 'datetime',
      'feed_label' => 'text',
      'feed_id' => 'integer',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @return array|null
   */
  public function getSettings() {
    return isset($this->setting[$this->getFeedId()]) ? $this->setting[$this->getFeedId()] : NULL;
  }

}
