<?php

namespace Drupal\custom_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Custom' Block.
 *
 * @Block(
 *   id = "events_switch_block",
 *   admin_label = @Translation("Events Switch Block"),
 *   category = @Translation("SITE Events Switch Block"),
 * )
 */
class EventsSwitchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $events_links_html = '<ul class="menu">
        <li><a href="/events" class="events-list">'. t("List") .'</a></li>
        <li><a href="/events-calendar" class="events-calendar">'. t("Calendar") .'</a></li>
      </ul>';

    return [
      '#type' => 'markup',
      '#markup' => $events_links_html,
    ];
  }

}
