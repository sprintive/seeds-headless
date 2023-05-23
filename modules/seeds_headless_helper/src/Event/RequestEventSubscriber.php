<?php

namespace Drupal\seeds_headless_helper\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class event subscriber.
 */
class RequestEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 1000];
    $events[KernelEvents::FINISH_REQUEST][] = ['onFinish', 1000];
    return $events;
  }

  /**
   * Sets the current route match.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    // Get NEXTJS-FORWARDED-IP from the request header.
    $ip = $request->headers->get('NEXTJS-FORWARDED-IP');

    if ($ip) {
      // Set the client IP to the NEXTJS-FORWARDED-IP.
      $request->server->set('REMOTE_ADDR', $ip);
    }
  }

  /**
   * On terminate.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onFinish(FinishRequestEvent $event) {
    $tags_to_invalidate = drupal_static('seeds_nextjs_tags_to_invalidate', []);
    $allow_clearing = drupal_static('seeds_nextjs_allow_clearing', FALSE);

    if (empty($tags_to_invalidate) || !$allow_clearing) {
      return;
    }
    $invalidate_url = \Drupal::config('seeds_headless_helper.settings')->get('invalidate_url') ?? 'http://localhost:3000/api/invalidate';
    $invalidate_secret = \Drupal::config('seeds_headless_helper.settings')->get('api_secret') ?? 'secret';
    try {
      \Drupal::httpClient()->post($invalidate_url, [
        'verify' => FALSE,
        'json' => [
          'tags' => $tags_to_invalidate,
          'secret' => $invalidate_secret,
        ],
        'timeout' => 5,
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('seeds_headless_helper')->error($e->getMessage());
      \Drupal::messenger()->addError(t('Failed to invalidate cache on NextJS server.'));
    }
  }

}
