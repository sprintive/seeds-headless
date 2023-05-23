<?php

namespace Drupal\seeds_headless_helper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Seeds nextjs settings form.
 */
class SeedsNextJsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'seeds_nextjs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'seeds_headless_helper.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('seeds_headless_helper.settings');

    $form['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Secret'),
      '#description' => $this->t('The API secret to invalidate cache tags.'),
      '#default_value' => $config->get('api_secret'),
    ];

    $form['invalidate_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invalidate URL'),
      '#description' => $this->t('The URL to invalidate cache tags.'),
      '#default_value' => $config->get('invalidate_url'),
    ];

    $form['nextjs_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NextJS Host'),
      '#description' => $this->t('The host of the nextjs server.'),
      '#default_value' => $config->get('nextjs_host'),
    ];

    $form['flush_cache_on_rebuild'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flush cache on rebuild'),
      '#description' => $this->t('Flush the cache on rebuild.'),
      '#default_value' => $config->get('flush_cache_on_rebuild'),
    ];

    $form['hide_untranslated_languages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide untranslated languages'),
      '#description' => $this->t('Hide languages that are not translated.'),
      '#default_value' => $config->get('hide_untranslated_languages'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('seeds_headless_helper.settings')
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('invalidate_url', $form_state->getValue('invalidate_url'))
      ->set('nextjs_host', $form_state->getValue('nextjs_host'))
      ->set('flush_cache_on_rebuild', $form_state->getValue('flush_cache_on_rebuild'))
      ->set('hide_untranslated_languages', $form_state->getValue('hide_untranslated_languages'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
