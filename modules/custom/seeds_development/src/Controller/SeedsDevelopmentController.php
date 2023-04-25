<?php

namespace Drupal\seeds_development\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\image\ImageStyleInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SeedsDevelopmentController.
 */
class SeedsDevelopmentController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The inspector.
   *
   * @var \Drupal\seeds_development\SeedsDevelopmentInspectorInterface
   */
  protected $inspector;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->inspector = $container->get('seeds_development.inspector');
    return $instance;
  }

  /**
   * Inpects an image style.
   *
   * @return array
   */
  public function inspectImageStyle(ImageStyleInterface $image_style) {

    $sections = $this->inspector->imageStyleUseability($image_style);
    $elements = [];
    foreach ($sections as $section) {

      $children = [];

      foreach ($section['content'] as $child) {
        $children[] = [
          '#type' => 'container',
          'link' => [
            '#type' => 'link',
            '#url' => $child['url'],
            '#title' => $child['label'],
          ],
        ];
      }

      $elements[$section['id']] = [
        '#type' => 'fieldset',
        '#title' => $section['title'],
        'children' => $children,
      ];
    }

    return $elements;
  }

  /**
   *
   */
  public function generateFieldGroups($entity_type_id, $bundle, $form_mode) {
    $tabs = (object) [
      'group_name' => 'group_tabs',
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'mode' => $form_mode,
      'context' => 'form',
      'children' => ['group_basic_information', 'group_media'],
      'parent_name' => "",
      'weight' => 1,
      'format_type' => 'tabs',
      'label' => 'Tabs',
      'region' => 'content',
      'format_settings' => [],
    ];

    $basic_information = (object) [
      'group_name' => 'group_basic_information',
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'mode' => $form_mode,
      'context' => 'form',
      'children' => ['langcode', 'description', 'body', 'field_body', 'field_text', 'field_text_1', 'field_link', 'field_taxonomy', 'field_taxonomy_1'],
      'parent_name' => "",
      'weight' => 1,
      'format_type' => 'tab',
      'label' => 'Basic Information',
      'region' => 'content',
      'format_settings' => [
        'label' => 'Basic Information',
        'id' => "",
        'classes' => '',
        'description' => '',
        'formatter' => 'closed',
        'required_fields' => 1,
      ],
    ];

    $media = (object) [
      'group_name' => 'group_media',
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'mode' => $form_mode,
      'context' => 'form',
      'children' => ['field_image', 'field_media', 'field_media_1'],
      'parent_name' => "",
      'weight' => 2,
      'format_type' => 'tab',
      'label' => 'Media',
      'region' => 'content',
      'format_settings' => [
        'label' => 'Media',
        'id' => "",
        'classes' => '',
        'description' => '',
        'formatter' => 'closed',
        'required_fields' => 1,
      ],
    ];

    field_group_group_save($tabs);
    field_group_group_save($basic_information);
    field_group_group_save($media);

    $this->messenger()->addStatus(t('Generated the field groups'));

    $definition = $this->entityTypeManager()->getDefinition($entity_type_id);

    return $this->redirect("entity.entity_form_display.$entity_type_id.default", [$definition->get('bundle_entity_type') => $bundle]);
  }

  /**
   * Viewdisplayinspect.
   *
   * @return string
   *   Return Hello string.
   */
  public function viewDisplayInspect($entity_type_id, $bundle, $view_mode) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: viewDisplayInspect with parameter(s): $entity_type_id, $bundle, $view_mode'),
    ];
  }

  /**
   * Inspects all image styles and their usability.
   *
   * @return array
   */
  public function inspectAllImageStyle() {
    // Get all image styles.
    /** @var \Drupal\image\ImageStyleInterface[] $image_styles */
    $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();

    // Foreach check their usability.
    $image_styles_info = [];
    foreach ($image_styles as $image_style) {
      $image_styles_info[$image_style->id()] = $this->inspector->imageStyleUseability($image_style);
    }

    // Build the rows in the table to show the results.
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Image Style'),
      ],
      '#rows' => [],
    ];

    foreach ($image_styles_info as $id => $image_style_info) {
      if (empty($image_style_info)) {
        $table['#rows'][] = [
          '#markup' => $id,
        ];
      }
    }

    $config_path = Settings::get('config_sync_directory');
    return [
      'desc' => [
        '#markup' => "<strong>NOTE: the application reads the local config ($config_path), not the active config,</strong>",
      ],
      'table' => $table,
    ];
  }

  private function generateTestFile() {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $image_path = \Drupal::moduleHandler()->getModule('seeds_development')->getPath() . '/assets/images/test.jpg';
    $path = $file_system->copy($image_path, 'public://responsive-image-test.jpg', FileSystemInterface::EXISTS_REPLACE);
    $file = File::create();
    $file->setFileUri($path);
    $file->setPermanent();
    $file->setFilename('responsive-image-test.jpg');
    $file->save();
    return $file->id();
  }

  /**
   * Tests all responsive image styles
   *
   * @return void
   */
  public function testResponsiveImages() {
    $key_value_store = \Drupal::keyValue('seeds_development');
    $file_id = $key_value_store->get('responsive_image_test_file');

    if (!$file_id) {
      $file_id = $this->generateTestFile();
      $key_value_store->set('responsive_image_test_file', $file_id);
    }

    $file = File::load($file_id);

    // Load all responsive image styles.
    $responsive_image_styles = ResponsiveImageStyle::loadMultiple();
    $render = [];
    foreach ($responsive_image_styles as $responsive_image_style) {
      $render[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['responsive-image-style'],
        ],
        'fieldset' => [
          '#type' => 'fieldset',
          '#title' => $responsive_image_style->label(),
          'responsive_image' => [
            '#theme' => 'responsive_image',
            '#responsive_image_style_id' => $responsive_image_style->id(),
            '#uri' => $file->getFileUri(),
          ],
        ],
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'responsive-image-style-list',
      ],
      'list' => $render,
      '#attached' => [
        'library' => ['seeds_development/responsive_image_style_test'],
      ],
    ];
  }

}