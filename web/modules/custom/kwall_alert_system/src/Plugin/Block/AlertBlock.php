<?php

namespace Drupal\kwall_alert_system\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Alert' Block.
 *
 * @Block(
 *   id = "alert_block",
 *   admin_label = @Translation("Alert Block"),
 *   category = @Translation("SITE Custom Block"),
 * )
 */
class AlertBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Provides wrapper to alerts.
    return [
      '#type' => 'inline_template',
      '#template' => '<div class="block-kwall-site-alert" style="display: none;">
                        <div class="arrow-container">
                          <!-- <button class="slick-arrow slick-prev">{{ prev }}<span class="fa fa-chevron-left"></span></button>
                          <button class="slick-arrow slick-next">{{ next }}<span class="fa fa-chevron-right"></span></button> -->
                        </div>
                        <div class="alerts-slick-carousel-alert style-wrap"></div>
                      </div>',
//      '#context' => [
//        'prev' => $this->t('Prev'),
//        'next' => $this->t('Prev')
//      ],
      '#attached' => [
        'library' => ['kwall_alert_system/alert-rest']
      ]
    ];
  }

}
