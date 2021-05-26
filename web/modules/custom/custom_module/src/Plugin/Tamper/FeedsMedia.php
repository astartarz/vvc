<?php

namespace Drupal\custom_module\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_module\KwallMigrationTrait;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Drupal\media\Entity\MediaType;

/**
 * Plugin implementation for media.
 *
 * @Tamper(
 *   id = "custom_media",
 *   label = @Translation("Create Media using Image URL"),
 *   description = @Translation("This will take image URL & create Media &
 *   return the media Id"), category = "Media"
 * )
 */
class FeedsMedia extends TamperBase {

  use KwallMigrationTrait;

  const SETTING_MEDIA = 'separator';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_MEDIA] = ',';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach (MediaType::loadMultiple() as $media_type) {
      $options[$media_type->id()] = $media_type->label();
    }

    $form[self::SETTING_MEDIA] = [
      '#type' => 'select',
      '#title' => $this->t('Media Type'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->getSetting(self::SETTING_MEDIA),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_MEDIA => $form_state->getValue(self::SETTING_MEDIA),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {

    $data = $item->getSource();
    $attributes = [];
    if (!empty($data["imageurl"])) {
      if (!empty($data["firstname"]) && !empty($data["lastname"])) {
        $alt_value = $data["firstname"] . $data["lastname"] . ' Photo';
        $attributes = [
          'title' => $alt_value,
          'alt' => $alt_value,
          'filename' => $data["firstname"] . $data["lastname"] . '.png',
        ];
      }
      $bundle = $this->getSetting(self::SETTING_MEDIA);
      $media = $this->createMedia($bundle, $data["imageurl"], $attributes);
      if (!empty($media)) {
        return [
          'target_id' => $media->id()
        ];
      }
    }
    return NULL;
  }

}




