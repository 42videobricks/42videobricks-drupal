<?php

namespace Drupal\videobricks\Traits;

use Api42Vb\Client\Api\VideosApi;
use Api42Vb\Client\ApiException;
use Api42Vb\Client\Configuration;
use Drupal\Core\Form\FormStateInterface;
use Drupal\videobricks\Form\VideobricksSettingsForm;
use GuzzleHttp\Client;

/**
 * Trait for common Videobricks related functionality.
 */
trait VideobricksTrait {

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateVideobricksElement(array $element, FormStateInterface $form_state) {
    $videoId = trim($element['#value']);
    $settings = \Drupal::configFactory()->get('videobricks.settings')->get('videobricks');
    $host = VideobricksSettingsForm::getHostByEnv($settings['environment']);
    $config = Configuration::getDefaultConfiguration();
    $config->setApiKey('x-api-key', $settings['api_key']);
    $config->setHost($host);

    $videosApi = new VideosApi(
      new Client(),
      $config
    );
    try {
      $videosApi->getVideoById($videoId);
      $form_state->setValueForElement($element, $videoId);
    }
    catch (ApiException $e) {
      \Drupal::logger('videobricks')->error($e->getMessage());
      $form_state->setError($element, t('Could not extract the 42videobricks Video ID. Please check the logs for more info.'));
    }
  }

}
