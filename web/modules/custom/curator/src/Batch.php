<?php
/**
 * Curator - Social post sync
 *
 * @package     curator
 * @author      Kwall <info@kwallcompany.com>
 * @license     GPL-2.0+
 * @link        http://www.kwallcompany.com/
 * @copyright   KwallCompany
 * Date:        06/26/2020
 * Time:        11:40 PM
 */

namespace Drupal\curator;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class Batch {

  use StringTranslationTrait;

  /**
   * Current batch object.
   *
   * @var array
   */
  protected $batch;

  /**
   * Batch constant
   */
  const BATCH_TITLE = 'Importing social post from curator';

  const BATCH_INIT_MESSAGE = 'Initializing batch...';

  const BATCH_ERROR_MESSAGE = 'An error has occurred. All posts might not be imported.';

  const BATCH_PROGRESS_MESSAGE = 'Processing @current out of @total .';

  const REGENERATION_FINISHED_MESSAGE = 'Posts are imported.';

  const REGENERATION_FINISHED_ERROR_MESSAGE = 'Importing social post batch finished with an error.';

  /**
   * Batch constructor.
   */
  public function __construct() {
    $this->batch = [
      'title' => $this->t(self::BATCH_TITLE),
      'init_message' => $this->t(self::BATCH_INIT_MESSAGE),
      'error_message' => $this->t(self::BATCH_ERROR_MESSAGE),
      'progress_message' => $this->t(self::BATCH_PROGRESS_MESSAGE),
      'operations' => [],
      'finished' => [__CLASS__, 'finishGeneration'],
    ];
  }

  /**
   * Run batch process.
   */
  public function start() {
    batch_set($this->batch);
  }

  /**
   * Prepare batch operations.
   *
   * @return $this
   */
  public function prepareOperations() {

    // Get all enable curator.
    $curators = \Drupal::service('curator.manager')
      ->getAvailableCurator();
    foreach ($curators as $curator) {
      // Get batch data.
      $data = \Drupal::service('curator.manager')->getBatchData($curator);
      if (!empty($data)) {
        foreach ($data as $key => $_data) {
          // Add batch operation.
          $this->addOperation($_data, $curator);
        }
      }
    }
    return $this;
  }

  /**
   * Adds an operation to the batch.
   *
   * @param $data
   * @param $curator
   */
  public function addOperation($data, $curator) {
    $this->batch['operations'][] = [
      __CLASS__ . '::generate',
      [$data, $curator],
    ];
  }

  /**
   * Batch callback function.
   *
   * @param $show
   * @param $key
   * @param $context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function generate($data, $curator, &$context) {
    // Import data to content type.
    \Drupal::service('curator.manager')
      ->setContext($context)
      ->import($data, $curator);
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @param $success
   * @param $results
   * @param $operations
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function finishGeneration($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addStatus(
        self::REGENERATION_FINISHED_MESSAGE
      );
    } else{
      \Drupal::messenger()->addStatus(
        self::REGENERATION_FINISHED_ERROR_MESSAGE
      );
    }
  }

}
