<?php

/**
 * @file
 * Contains \Drupal\ccsf_content_migration\EventSubscriber\MyModuleRedirectSubscriber
 */

namespace Drupal\custom_module\EventSubscriber;

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
    if ($request->attributes->get('exception')) {
      $url = $request->getRequestUri();
      $absoluteUrl = 'http://www.vvc.edu' . $url;

      $query = \Drupal::database()->select('node__field_legacy_path', 'p');
      $query->condition('field_legacy_path_value', [$url, $absoluteUrl], 'IN');
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
