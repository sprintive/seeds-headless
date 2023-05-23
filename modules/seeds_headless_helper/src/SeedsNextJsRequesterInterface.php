<?php

namespace Drupal\seeds_headless_helper;

/**
 * Interface SeedsNextJsRequesterInterface.
 */
interface SeedsNextJsRequesterInterface {

  /**
   * Request to the nextjs server.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function request($uri, $method = 'GET', $options = []);

}
