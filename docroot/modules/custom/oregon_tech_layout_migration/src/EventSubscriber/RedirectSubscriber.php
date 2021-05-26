<?php

/**
 * @file
 */

namespace Drupal\oregon_tech_layout_migration\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return([
      KernelEvents::REQUEST => [
        ['redirectUrls'],
      ]
    ]);
  }

  /**
   * Redirect requests for my_content_type node detail pages to node/123.
   *
   * @param GetResponseEvent $event
   * @return void
   */
  public function redirectUrls(GetResponseEvent $event) {

    $request = $event->getRequest();
    if ($request->attributes->get('_route') == 'entity.node.canonical') {
      $node = $request->attributes->get('node');
      if ($node->label() == 'Page Not Found') {
        $url = $request->getRequestUri();
        $url = rtrim($url, '/');

        $query = \Drupal::database()->select('node__field_legacy_path', 'p');
        $query->condition('field_legacy_path_value', "%" . \Drupal::database()->escapeLike($url) . "%", 'LIKE');
        $query->addField('p', 'entity_id');
        $nid = $query->execute()->fetchField();

        if ($nid) {
          $redirect_url = Url::fromUri('entity:node/' . $nid);
          $response = new RedirectResponse($redirect_url->toString(), 301);
          $response->send();
          return;
        }
      }
    }

  }

}
