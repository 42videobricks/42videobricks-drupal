<?php

namespace Drupal\videobricks\Plugin\media\Source;

use Api42Vb\Client\Api\VideosApi;
use Api42Vb\Client\Configuration;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\videobricks\Form\VideobricksSettingsForm;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Videobricks Media Source.
 *
 * @MediaSource(
 *   id = "videobricks_media",
 *   label = @Translation("42videobricks"),
 *   description = @Translation("Use Videobricks for reusable media."),
 *   allowed_field_types = {"videobricks"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   forms = {
 *     "media_library_add" = "Drupal\videobricks\Form\VideobricksMediaForm"
 *   }
 * )
 */
class Videobricks extends MediaSourceBase {

  const META_THUMBNAIL = 'thumbnail_uri';
  const META_VIDEO_ID = 'video_id';
  const META_DEFAULT_NAME = 'default_name';

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Videobricks global config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $videobricksSettings;

  /**
   * Construct.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManagerInterface $entity_type_manager,
      EntityFieldManagerInterface $entity_field_manager,
      FieldTypePluginManagerInterface $field_type_manager,
      ConfigFactoryInterface $config_factory,
      StreamWrapperManagerInterface $stream_wrapper_manager,
      FileSystemInterface $file_system,
      ClientInterface $http_client,
      LoggerChannelFactoryInterface $logger_channel_factory,
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
    $this->logger = $logger_channel_factory->get('videobricks');
    $this->videobricksSettings = $config_factory->get(VideobricksSettingsForm::SETTINGS);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      static::META_THUMBNAIL => $this->t('Thumbnail'),
      static::META_VIDEO_ID => $this->t('Video ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    // Get the file and image data.
    /** @var \Drupal\Core\Field\FieldItemListInterface $sourceField */
    $sourceField = $media->get($this->configuration['source_field']);

    // If the source field is not required, it may be empty.
    if (!$sourceField || $sourceField->isEmpty()) {
      return parent::getMetadata($media, $name);
    }

    $videoId = $sourceField->getValue()[0]['video_id'];
    $settings = $this->videobricksSettings->get('videobricks');
    $host = VideobricksSettingsForm::getHostByEnv($settings['environment']);
    $config = Configuration::getDefaultConfiguration();
    $config->setApiKey('x-api-key', $settings['api_key']);
    $config->setHost($host);

    try {
      $videosApi = new VideosApi(
        new Client(),
        $config
      );
      $video = $videosApi->getVideoById($videoId);
      $directory = 'public://';
      $localThumbnailUri = "$directory" . Crypt::hashBase64($video->getId()) . '.jpg';

      switch ($name) {
        case self::META_DEFAULT_NAME:
          return $video->getTitle();

        case self::META_THUMBNAIL:
          try {
            $response = $this->httpClient->get($video->getAssets()->getThumbnail());
            if ($response->getStatusCode() === 200) {
              $this->fileSystem->saveData((string) $response->getBody(), $localThumbnailUri, FileSystemInterface::EXISTS_REPLACE);
              return $localThumbnailUri;
            }
          }
          catch (RequestException $e) {
            $this->logger->warning('Could not download remote thumbnail from {url}.', [
              'url' => $video->getAssets()->getThumbnail(),
            ]);
          }
      }

      return parent::getMetadata($media, $name);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage() . ' for the videoId : {videoId}', [
        'videoId' => $videoId,
      ]);
      throw new \Exception('Video not found');
    }

    return NULL;
  }

}
