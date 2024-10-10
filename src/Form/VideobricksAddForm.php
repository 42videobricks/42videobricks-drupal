<?php

namespace Drupal\videobricks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * General settings form 42videobricks API.
 */
class VideobricksAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'videobricks_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#type' => 'file',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'avi mov mp4 mpeg mpg mxf ts',
        ],
      ],
    ];
    $form['#theme'] = 'videobricks_add_new_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
