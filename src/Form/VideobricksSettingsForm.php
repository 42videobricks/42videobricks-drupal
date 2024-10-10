<?php

namespace Drupal\videobricks\Form;

use Api42Vb\Client\Api\VideosApi;
use Api42Vb\Client\ApiException;
use Api42Vb\Client\Configuration;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;

/**
 * General settings form 42videobricks API.
 */
class VideobricksSettingsForm extends ConfigFormBase {

  /**
   * The config settings name.
   *
   * @var string Config settings
   */

  const SETTINGS = 'videobricks.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'videobricks_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS)->get('videobricks');
    $form['videobricks'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('42videobricks credentials'),
      '#tree' => TRUE,
    ];

    $form['videobricks']['api_key'] = [
      '#title' => $this->t('Api key'),
      '#type' => 'textfield',
      '#description' => $this->t('The api key from 42videobricks'),
      '#required' => TRUE,
      '#default_value' => !empty($config['api_key']) ? $config['api_key'] : '',
    ];
    $options['sandbox'] = 'Sandbox';
    $options['staging'] = 'Staging';
    $options['production'] = 'Production';
    $form['videobricks']['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Select your environment'),
      '#description' => $this->t('The environment you would like to connect to.'),
      '#options' => $options,
      '#default_value' => !empty($config['environment']) ? $config['environment'] : 'sandbox',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    $hostname = self::getHostByEnv($values['videobricks']['environment']);
    $config = Configuration::getDefaultConfiguration();
    $config->setApiKey('x-api-key', $values['videobricks']['api_key']);
    $config->setHost($hostname);
    $videosApi = new VideosApi(
      new Client(),
      $config
    );
    try {
      $videosApi->getVideos();
    }
    catch (ApiException $e) {
      $form_state->setErrorByName('api_key', $this->t('The api key is invalid'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config(static::SETTINGS)
      ->set("videobricks", $values['videobricks'])
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the 42videobricks host by environment name.
   */
  public static function getHostByEnv($env) {
    switch ($env) {
      case 'sandbox':
        $host = 'https://api-sbx.42videobricks.com';
        break;

      case 'staging':
        $host = 'https://api-stg.42videobricks.com';
        break;

      case 'production':
        $host = 'https://api.42videobricks.com';
        break;

      default:
        $host = 'https://api-sbx.42videobricks.com';
    }
    return $host;
  }

}
