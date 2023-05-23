<?php
namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Bundle label computed field.
 */
class BundleRedirectComputedField extends FieldItemList
{
  use ComputedItemListTrait;
  use SeedsComputedFieldTrait;

  private $redirect_bundle = ["slider"];

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


    $entity = $this->prepareEntity($entity);
    $bundle = $entity->bundle();
    
    if (in_array($bundle, $this->redirect_bundle)) {
      return $this->list[0] = $this->createItem(0, TRUE);
    } else {
      // NULL.
      return $this->list[0] = $this->createItem(0, FALSE);
    }
  }
}
