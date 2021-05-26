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


namespace Drupal\curator\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Curator.
 */
class CuratorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['enable'] = $this->t('Enabled');
    $header['label'] = $this->t('Feed');
    $header['id'] = $this->t('Machine name');
    $header['content_type'] = $this->t('Content Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['enable'] = $entity->isEnabled() ? 'Yes' : 'No';
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['content_type'] = $entity->getContentType();

    return $row + parent::buildRow($entity);
  }

}
