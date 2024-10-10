<?php

namespace Drupal\videobricks\Controller;

use Api42Vb\Client\Api\VideosApi;
use Api42Vb\Client\ApiException;
use Api42Vb\Client\Configuration;
use Api42Vb\Client\Model\VideoMultipartUploadFinalize;
use Api42Vb\Client\Model\VideoMultipartUploadFinalizePartsInner;
use Api42Vb\Client\Model\VideoMultipartUploadInit;
use Api42Vb\Client\Model\VideoProperties;
use Drupal\Core\Controller\ControllerBase;
use Drupal\videobricks\Form\VideobricksSettingsForm;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Library controller.
 */
class VideobricksVideoController extends ControllerBase {

  /**
   * Initialize the video creation on 42videobricks side.
   *
   * Return []
   */
  public function init(Request $request) {

    if (empty($request->get('name')) || empty($request->get('size'))) {
      return new JsonResponse('Bad request', 400);
    }

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
      $size = $request->get('size');
      $data['title'] = $request->get('name');
      $data['public'] = TRUE;
      $video_props = new VideoProperties($data);
      $videoResponse = $videosApi->addVideo($video_props);
      $id = $videoResponse->getId();
      $multiPartInit = new VideoMultipartUploadInit(
        [
          'name' => $request->get('name'),
          'size' => $size,
        ]
      );
      $multiParts = $videosApi->initMultipartUploadVideoById($id, $multiPartInit);
      $response['response'] = $multiParts->jsonSerialize();
      $response['videoId']  = $id;
      return new JsonResponse($response, 200);
    }
    catch (ApiException $e) {
      return new JsonResponse($e->getMessage(), $e->getCode());
    }
  }

  /**
   * Finalize the upload of the file on 42videobricks side.
   *
   * Return []
   */
  public function finalize(Request $request) {

    if (empty($request->get('videoId')) || empty($request->get('fileId')) ||
      empty($request->get('fileKey')) || empty($request->get('parts'))) {
      return new JsonResponse('Bad request', 400);
    }

    $settings = \Drupal::configFactory()->get('videobricks.settings')->get('videobricks');
    $host     = VideobricksSettingsForm::getHostByEnv($settings['environment']);

    $config = Configuration::getDefaultConfiguration();
    $config->setApiKey('x-api-key', $settings['api_key']);
    $config->setHost($host);

    $videosApi = new VideosApi(
      new Client(),
      $config
    );
    try {
      $fileId = $request->get('fileId');
      $fileKey = $request->get('fileKey');
      $postParts = $request->get('parts');
      $videoId = $request->get('videoId');
      foreach ($postParts as $delta => $part) {
        $partObject = new VideoMultipartUploadFinalizePartsInner();
        $partObject->setPartNumber((int) $part['PartNumber']);
        $partObject->setETag($part['ETag']);
        $finalParts[$delta] = $partObject;
      }
      if (!empty($finalParts)) {
        $multiPartUpload = new VideoMultipartUploadFinalize(
          [
            'file_id'  => $fileId,
            'file_key' => $fileKey,
            'parts'    => $finalParts,
          ]
        );
        $videosApi->finalizeMultipartUploadVideoById($videoId, $multiPartUpload);
      }
    }
    catch (ApiException $e) {
      $videoId = NULL;
      return new JsonResponse($e->getMessage(), $e->getCode());
    }
    return new JsonResponse('Video created with ID : ' . $videoId, 200);
  }

}
