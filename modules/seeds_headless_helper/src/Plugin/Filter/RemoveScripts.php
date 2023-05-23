<?php

namespace Drupal\seeds_headless_helper\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to remove scripts.
 *
 * @Filter(
 *   id = "remove_scripts",
 *   title = @Translation("Remove any script"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class RemoveScripts extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $text . '</body></html>');
    $node = $dom->getElementsByTagName('body')->item(0);
    $xpath = new \DOMXPath($dom);
    $scripts = $xpath->query('//script');
    foreach ($scripts as $script) {
      $script->parentNode->removeChild($script);
    }
    $result = new FilterProcessResult($dom->saveHTML($node));
    return $result;
  }

}
