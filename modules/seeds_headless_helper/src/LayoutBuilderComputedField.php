<?php
namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\OverridesSectionStorageInterface;

/**
 * Layout builder computed field.
 */
class LayoutBuilderComputedField extends FieldItemList implements JSONComputedFieldInterface {
  use ComputedItemListTrait;
  use LayoutEntityHelperTrait;
  use SeedsComputedFieldTrait;

  /**
   * {@inheritDoc}
   */
  public function access($operation = 'view', ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    // Check if the entity has layout.
    if ($entity->isNew()) {
      return;
    }
    $cacheability = new CacheableMetadata();
    $entity = $this->prepareEntity($entity);

    $section_storage = $this->getSectionStorageForEntity($entity);

    if (!$section_storage) {
      return NULL;
    }

    if ($section_storage instanceof OverridesSectionStorageInterface) {
      /** @var \Drupal\layout_builder\Field\LayoutSectionItemList $layout */
      $layout = $entity->layout_builder__layout;
      $sections = $layout->getSections();
    } else {
      $sections = $section_storage->getSections();
    }

    $serailized = [];
    foreach ($sections as $section) {
      $section = $section->toArray();
      foreach ($section['components'] as $key => $component) {
        $id = $component['configuration']['id'] ?? NULL;
        $props = [];
        if ($id) {
          // Load plugin block.
          try {
            $plugin = \Drupal::service('plugin.manager.block')->createInstance($id);
            $cacheability->addCacheableDependency($plugin);
            if ($plugin instanceof NextJsPluginInterface) {
              $props = $plugin->getProperties();
            }
          } catch (\Exception $e) {
            \Drupal::logger('seeds_headless_helper')->error($e->getMessage());
            // Do nothing.
          }

          $section['components'][$key]['props'] = $props;
        }
        if (str_starts_with($component['configuration']['id'], 'inline_block')) {
          /** @var  Drupal\Block_content_entity\BlockContent $block */
          $block = \Drupal::entityTypeManager()->getStorage('block_content')->loadRevision($component["configuration"]["block_revision_id"]);
          if ($block) {
            $section['components'][$key]['configuration']['type'] = $block->bundle();
            $section['components'][$key]['configuration']['uuid'] = $block->uuid();

            // Cache.
            $cacheability->addCacheableDependency($block);
          }
        }

        if (str_starts_with($component['configuration']['id'], 'block_content')) {
          /** @var  Drupal\Block_content_entity\BlockContent $block */
          $uuid = explode(":", $component['configuration']['id'])[1];
          $blocks = \Drupal::entityTypeManager()->getStorage('block_content')->loadByProperties(['uuid' => $uuid]);
          $block = reset($blocks);
          $section['components'][$key]['configuration']['type'] = $block->bundle();
          $section['components'][$key]['configuration']['uuid'] = $block->uuid();
        }
      }
      $serailized[] = $section;
    }
    /** @var \Drupal\seeds_headless_helper\Plugin\Field\FieldType\CacheableStringItem $item */
    $item = $this->createItem(0, json_encode([
      'sections' => $serailized,
      'cache_tags' => $cacheability->getCacheTags(),
    ]));
    /** @var \Drupal\Core\Cache\RefinableCacheableDependencyInterface $property */
    $property = $item->get('value');
    $property->addCacheableDependency($cacheability);
    $this->list[0] = $item;
  }
}
