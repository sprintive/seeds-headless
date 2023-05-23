<?php

namespace Drupal\seeds_headless_helper\Plugin\WebformElement;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'poll_select' element.
 *
 * @WebformElement(
 *   id = "poll_select",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tableselect.php/class/Tableselect",
 *   label = @Translation("Questions and Answers (Select)"),
 *   description = @Translation("Provides a form element for a table with select options of questions and answers."),
 *   category = @Translation("Options elements"),
 * )
 */
class PollSelect extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'questions_answers' => '',
      'questions' => '',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['element']['questions_answers'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Answers'),
    ];

    $form['element']['questions'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Questions'),
    ];
    return $form;
  }

  /**
   *
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    $element['#element_validate'][] = [$this, 'validatePollAnswers'];
  }

  /**
   *
   */
  public function validatePollAnswers(&$element, FormStateInterface $form_state) {
    $value = $element['#value'] ?? "";
    $decoded = Yaml::decode($value);

    // Check if there is value.
    if (!$decoded && $element['#required']) {
      $form_state->setError($element, $this->t('This field is required.'));
    }

    if ($decoded) {
      $questions = Yaml::decode($element['#questions']);
      $answers = Yaml::decode($element['#questions_answers']);
      // Check if all the questions have been answered.
      foreach ($decoded as $question => $answer) {
        if (!isset($questions[$question]) || !isset($answers[$answer])) {
          $form_state->setError($element, $this->t('Questions or answers are not valid'));
          return;
        }
      }

      // If required, check if all the questions have been answered.
      if ($element["#required"]) {
        foreach (array_keys($questions) as $question_key) {
          if (!isset($decoded[$question_key])) {
            $form_state->setError($element, $this->t('There are questions which have not been answered.'));
            return;
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function format($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $key = $element["#webform_key"];
    $value = $webform_submission->getElementData($key);
    $data = Yaml::decode($value);
    $answers = Yaml::decode($element['#questions_answers']);
    $questions = Yaml::decode($element['#questions']);
    if ($type === 'Html') {
      $table = [
        '#type' => 'table',
        '#header' => [
          $this->t('Question'),
          $this->t('Answer'),
        ],
        '#empty' => $this->t('No available answers.'),
        '#rows' => [],
      ];

      $rows = &$table['#rows'];
      foreach ($data as $question_key => $answer_key) {
        $rows[] = [
          $questions[$question_key], $answers[$answer_key],
        ];
      }
      if (!isset($options['view_mode'])) {
        return NULL;
      }

      return $table;
    }
    else {
      $results = [''];
      foreach ($data as $question_key => $answer_key) {
        $results[] = "$questions[$question_key] = $answers[$answer_key]";
      }

      return implode(PHP_EOL, $results);
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return ['questions', 'questions_answers', 'title',
      'label',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function postCreate(array &$element, WebformSubmissionInterface $webform_submission) {
    $key = $element["#webform_key"];
    $value = $webform_submission->getElementData($key);
    $yaml = Yaml::encode($value);
    $webform_submission->setElementData($key, $yaml);
  }

  /**
   * {@inheritDoc}
   */
  public function getTableColumn(array $element) {
    return [];
  }

}
