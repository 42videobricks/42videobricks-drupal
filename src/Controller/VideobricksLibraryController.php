<?php

namespace Drupal\videobricks\Controller;

use Api42Vb\Client\Api\VideosApi;
use Api42Vb\Client\ApiException;
use Api42Vb\Client\Configuration;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\videobricks\Form\VideobricksSettingsForm;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

/**
 * Library controller.
 */
class VideobricksLibraryController extends ControllerBase {

  /**
   * Returns a renderable array for the library listing videos page.
   *
   * Return []
   */
  public function list(Request $request) {
    $settings = \Drupal::configFactory()->get('videobricks.settings')->get('videobricks');
    $host = VideobricksSettingsForm::getHostByEnv($settings['environment']);
    $config = Configuration::getDefaultConfiguration();
    $config->setApiKey('x-api-key', $settings['api_key']);
    $config->setHost($host);

    $videosApi = new VideosApi(
      new Client(),
      $config
    );
    $header = [
      'thumbnail' => t('Thumbnail'),
      'title' => t('Title'),
      'shortcode' => t('Video ID'),
      'privacy' => t('Privacy'),
      'edit' => t('Edit'),
    ];
    try {
      $rows = [];
      if (!empty($request->get('s'))) {
        $videos = $videosApi->getVideos(NULL, NULL, $request->get('s'));
      }
      else {
        $videos = $videosApi->getVideos();
      }
      foreach ($videos->getData() as $key => $video) {
        $image = Markup::create('<img src="' . $video->getAssets()->getThumbnail() . '"alt="thumbmnail" width="250px">');
        $rows[$key]['thumbnail'] = $image;
        $rows[$key]['title'] = stripslashes($video->getTitle());
        $svg = '<a class"copy-shortcode" style="position:relative" data-shortcode="' . strip_tags($video->getId()) . '"><svg xmlns="http://www.w3.org/2000/svg" height="1.5em" viewBox="0 0 448 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><style>svg{fill:#2855a4}</style><path d="M384 336H192c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16l140.1 0L400 115.9V320c0 8.8-7.2 16-16 16zM192 384H384c35.3 0 64-28.7 64-64V115.9c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1H192c-35.3 0-64 28.7-64 64V320c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H256c35.3 0 64-28.7 64-64V416H272v32c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192c0-8.8 7.2-16 16-16H96V128H64z"/></svg></a>';
        $rows[$key]['shortcode'] = Markup::create($svg . '<input class="videobricks" id="videobricks" type="text" readonly value="' . strip_tags($video->getId()) . '"');
        if ($video->getPublic()) {
          $rows[$key]['public'] = t('Public');
          $rows[$key]['shortcode'] = Markup::create($svg . '<input class="videobricks" id="videobricks" type="text" readonly value="' . strip_tags($video->getId()) . '"');
        }
        else {
          $rows[$key]['public'] = t('Private');
          $rows[$key]['shortcode'] = t('Your video is private');
        }
        if ($video->getStatus() !== 'AVAILABLE') {
          $rows[$key]['shortcode'] = t('Your video will be ready soon. Please reload this page in a few minutes. In case of troubleshooting, please check 42videobricks administration page.');
        }
        $rows[$key]['edit'] = Markup::create(sprintf('<a href="https://admin.42videobricks.com/%s/videos/%s" target="_blank">Edit</a>', $settings['environment'], $video->getId()));
      }

    }
    catch (ApiException $e) {
    }
    $perPage = 10;
    $pageManager = \Drupal::service('pager.manager');
    $pager = $pageManager->createPager(count($rows), $perPage);
    $chunks = array_chunk($rows, $perPage, TRUE);
    $build['search'] = [
      '#type' => '#markup',
      '#markup' => Markup::create('<form><input type="search" name="s" placeholder="Search for videos" value="' . $request->get('s') . '"><input type="submit" id="videobricks-search" value="Search" class="button--small button--primary"></form>'),
    ];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $chunks[$pager->getCurrentPage()],
      '#empty' => t('No content has been found.'),
    ];
    $build['pager'] = [
      '#type' => 'pager',
      '#quantity' => count($rows),
    ];
    $build['#attached']['library'][] = 'videobricks/drupal.videobricks.copy';

    return [
      '#type' => '#markup',
      '#markup' => \Drupal::service('renderer')->render($build),
    ];
  }

}
