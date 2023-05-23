<?php

namespace Drupal\seeds_headless_helper\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to always set width and height for images.
 *
 * @Filter(
 *   id = "filter_image_width_height",
 *   title = @Translation("Always set width and height to images"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterImageWidthHeight extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Create dom document with utf-8 support.
    $dom = new \DOMDocument('1.0', 'UTF-8');
    // Load html with utf-8.
    $dom->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $text . '</body></html>');
    $node = $dom->getElementsByTagName('body')->item(0);
    // Xpath.
    $xpath = new \DOMXPath($dom);
    // Get all images.
    $images = $xpath->query('//img');
    // Loop through images, check if they have width and height, if not, set it automatically.
    foreach ($images as $image) {
      // Get image width.
      $width = $image->getAttribute('width');
      // Get image height.
      $height = $image->getAttribute('height');

      // If width or height is empty.
      if (empty($width) || empty($height)) {
        // Get image src.
        $src = DRUPAL_ROOT . urldecode($image->getAttribute('src'));
        // Get image size.
        $size = getimagesize($src);
        if ($size) {
          // Set image width.
          $image->setAttribute('width', $size[0]);
          // Set image height.
          $image->setAttribute('height', $size[1]);
        }
        else {
          // Set image width and height to 100%.
          $image->setAttribute('width', '100%');
          $image->setAttribute('height', '100%');
        }

      }
    }

    // Convert to html text.
    $new_text = '';
    foreach ($node->childNodes as $child) {
      $new_text .= $dom->saveHTML($child);
    }
    return new FilterProcessResult($new_text);
  }

}
