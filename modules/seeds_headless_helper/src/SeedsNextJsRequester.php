<?php

namespace Drupal\seeds_headless_helper;

/**
 * Class SeedsNextJsRequester.
 */
class SeedsNextJsRequester implements SeedsNextJsRequesterInterface {

  /**
   * {@inheritDoc}
   */
  public function request($uri, $method = 'GET', $options = []) {
    $nextjs_host = \Drupal::config('seeds_headless_helper.settings')->get('nextjs_host') ?? 'http://localhost:3000';
    $invalidate_secret = \Drupal::config('seeds_headless_helper.settings')->get('api_secret') ?? 'secret';
    $options['headers']['DRUPAL-API-SECRET'] = $invalidate_secret;
    $options['timeout'] = $options['timeout'] ?? 5;
    // Verify ssl.
    $options['verify'] = FALSE;
    $full_url = trim($nextjs_host, '/') . '/' . trim($uri, '/');
    return \Drupal::httpClient()->post($full_url, $options);
  }

}
