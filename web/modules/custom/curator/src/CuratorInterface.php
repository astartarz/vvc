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

namespace Drupal\curator;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an curator entity.
 */
interface CuratorInterface extends ConfigEntityInterface {

  /**
   * Return feed id choose for this entity.
   *
   * @return string
   */
  public function getFeedId();

  /**
   * Is enabled on this entity.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Get limit.
   *
   * @return int
   */
  public function limit();

  /**
   * Get enabled content type.
   *
   * @return string
   */
  public function getContentType();

  /**
   * Mapping list for fields.
   *
   * @return mixed
   */
  public function getMappingList();
}
