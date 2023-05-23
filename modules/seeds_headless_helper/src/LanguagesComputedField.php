<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Languages computed field.
 */
class LanguagesComputedField extends FieldItemList implements JSONComputedFieldInterface {
  use ComputedItemListTrait;
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
    $links = [];
    $entity = $this->getEntity();

    // If the entity is new, return.
    if ($entity->isNew()) {
      return;
    }

    $entity = $this->prepareEntity($entity);
    $hide_untranslated_languages = (bool) \Drupal::config('seeds_headless_helper.settings')->get('hide_untranslated_languages');
    // If the entity is translatable.
    if ($entity instanceof TranslatableInterface) {
      // Get all languages.
      $languages = \Drupal::languageManager()->getLanguages();
      // Foreach languages, create links.
      foreach ($languages as $language) {
        if ($hide_untranslated_languages && !$entity->hasTranslation($language->getId())) {
          continue;
        }
        
        // Hide unpublished entities.
        $translation = $entity->hasTranslation($language->getId()) ? $entity->getTranslation($language->getId()) : NULL;
        if (($translation instanceof EntityPublishedInterface) && !$translation->isPublished()) {
          continue;
        }

        $links[] = [
          'href' => $entity->toUrl('canonical', ['language' => $language])->toString(),
          'lang' => $language->getId(),
          'label' => $language->getName(),
        ];
      }
    }

    // Json encode the links.
    $this->list[0] = $this->createItem(0, json_encode($links));
  }

}
