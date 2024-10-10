<?php

namespace Drupal\videobricks\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;
use Drupal\videobricks\Traits\VideobricksTrait;

/**
 * Overrides the Videobricks Media Add form.
 */
class VideobricksMediaForm extends AddFormBase {
  use VideobricksTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_videobricks';
  }

  /**
   * {@inheritdoc}
   */
  public function buildInputElement(array $form, FormStateInterface $form_state) {
    $form['container'] = [
      '#type' => 'container',
    ];
    $form['container']['video_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Video ID'),
      '#description' => $this->t('Paste here the Videobricks Video ID'),
      '#rows' => '2',
      '#element_validate' => [[static::class, 'validateVideobricksElement']],
    ];
    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#submit' => ['::addButtonSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    $form['container']['#attributes']['class'][] = 'container-inline';
    return $form;
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([[
      'video_id' => $form_state->getValue('video_id'),
    ],
    ], $form, $form_state);
  }

}
