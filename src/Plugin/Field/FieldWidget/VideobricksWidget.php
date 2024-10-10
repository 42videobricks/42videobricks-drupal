<?php

namespace Drupal\videobricks\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'videobricks' field widget.
 *
 * @FieldWidget(
 *   id = "videobricks",
 *   label = @Translation("Videobricks"),
 *   field_types = {"videobricks"},
 * )
 */
class VideobricksWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['video_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Video ID'),
      '#description' => $this->t('Paste here the Videobricks Video ID'),
      '#rows' => 3,
      '#default_value' => $items[$delta]->video_id ?? NULL,
    ];
    return $element;
  }

}
