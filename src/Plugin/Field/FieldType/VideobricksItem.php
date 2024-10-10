<?php

namespace Drupal\videobricks\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'videbricks' field type.
 *
 * @FieldType(
 *   id = "videobricks",
 *   label = @Translation("Videobricks"),
 *   category = @Translation("General"),
 *   default_widget = "videobricks",
 *   default_formatter = "videobricks_default"
 * )
 */
class VideobricksItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->video_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['video_id'] = DataDefinition::create('string')->setLabel(t('Video ID'));
    return $properties;
  }

  /**
   * {@inheritDoc}
   */
  public static function mainPropertyName() {
    return 'video_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $options['video_id']['NotBlank'] = [];
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', $options);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [
      'video_id' => [
        'type' => 'varchar',
        'length' => 255,
      ],
    ];

    return [
      'columns' => $columns,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['video_id'] = $random->word(mt_rand(1, 10));
    return $values;
  }

}
