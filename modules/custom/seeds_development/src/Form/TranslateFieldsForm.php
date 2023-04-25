<?php

namespace Drupal\seeds_development\Form;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 */
class TranslateFieldsForm extends FormBase implements TrustedCallbackInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   *
   */
  public static function trustedCallbacks() {
    return ['preRenderTable'];
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'seeds_translate_fields_form';
  }

  /**
   * Gets the entity type id from current route.
   *
   * @return string
   */
  protected function getEntityTypeId() {
    return $this->routeMatch->getParameter('entity_type_id');
  }

  /**
   * Gets the bundle from current route.
   *
   * @return string
   */
  protected function getBundle() {
    return $this->routeMatch->getParameter('bundle');
  }

  /**
   * Gets the fields to translate.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface[]
   */
  protected function getFields() {
    $entity_type_id = $this->routeMatch->getParameter('entity_type_id');
    $bundle = $this->routeMatch->getParameter('bundle');
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $translatable_fields = [];
    foreach ($fields as $field) {
      if ($field instanceof FieldConfigInterface) {
        $translatable_fields[] = $field;
      }
    }

    return $translatable_fields;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $fields = $this->getFields();
    $languages = $this->languageManager->getLanguages();
    if (count($languages) < 2) {
      throw new NotFoundHttpException();
    }

    $table = [
      '#type' => 'container',
      '#pre_render' => [[$this, 'preRenderTable']],
      '#tree' => TRUE,
    ];

    foreach ($fields as $field) {
      $row = [];
      $row['name'] = sprintf("%s (%s)", $field->getLabel(), $field->getName());

      foreach ($languages as $language) {
        $this->languageManager->setConfigOverrideLanguage($language);
        $field = FieldConfig::load($field->id());
        $row[$language->getId()] = [
          'label' => [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#default_value' => $field->getLabel(),
          ],
        ];
      }

      $table[$field->getName()] = $row;
    }

    return [
      'fields' => $table,
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Save'),
        ],
      ],
    ];
  }

  /**
   *
   */
  public function preRenderTable(&$fields) {
    $languages = $this->languageManager->getLanguages();
    $fields['#theme'] = 'table';
    $header = [
      'Name',
    ];

    foreach ($languages as $language) {
      $header[] = $language->getName();
    }

    $fields['#header'] = $header;
    foreach (Element::children($fields) as $child) {
      $child = $child;
      $fields['#rows'][$child] = [
        'name' => $fields[$child]['name'],
      ];

      foreach ($languages as $language) {
        $fields['#rows'][$child][$language->getId()] = [
          'data' => $fields[$child][$language->getId()],
        ];
      }

      unset($fields[$child]);
    }

    return $fields;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fields = $form_state->getValue('fields', []);
    $languages = $this->languageManager->getLanguages();
    $entity_type_id = $this->routeMatch->getParameter('entity_type_id');
    $bundle = $this->routeMatch->getParameter('bundle');
    $original_langcode = $this->languageManager->getDefaultLanguage()->getId();
    foreach ($fields as $field_name => $field) {
      $config_name = "field.field.$entity_type_id.$bundle.$field_name";
      $field_config = $this->configFactory()->getEditable($config_name);
      $base_langcode = $field_config->get('langcode') ?? $original_langcode;
      foreach ($languages as $language) {
        $translation = $this->languageManager->getLanguageConfigOverride($language->getId(), $config_name);

        if ($base_langcode == $translation->getLangcode()) {
          $translation->delete();
          $translation = $field_config;
          $translation->set('langcode', $original_langcode);
        }

        $field_values = $field[$language->getId()];
        foreach ($field_values as $config_key => $config_value) {
          $translation->set($config_key, $config_value);
        }
        $translation->save();
      }
    }

  }

}
