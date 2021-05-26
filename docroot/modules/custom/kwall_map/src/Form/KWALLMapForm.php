<?php

namespace Drupal\kwall_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class KWALLMapForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'kwall_map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kwall_map_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('kwall_map.settings');


    $form['style'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON styles'),
      '#description' => $this->t('A JSON encoded styles array to customize the presentation of the Google Map.'),
      '#default_value' => $config->get('style'),
    ];

    $form['overlay_path_0'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campus Overlay path'),
      '#description' => $this->t('Set relative or absolute path to custom overlay. Tokens supported. Empty for default.'),
      '#default_value' => $config->get('overlay_path_0'),
    ];

    $form['ne_lat_0'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campus Northeast Latitude'),
      '#description' => $this->t('Set the Northeast Latitude coordinates. Empty for default.'),
      '#default_value' => $config->get('ne_lat_0'),
    ];
    $form['ne_lon_0'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campus Northeast Longitude'),
      '#description' => $this->t('Set the Northeast Longitude coordinates. Empty for default.'),
      '#default_value' => $config->get('ne_lon_0'),
    ];
    $form['sw_lat_0'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campus Southwest Latitude'),
      '#description' => $this->t('Set the Southwest Latitude coordinates. Empty for default.'),
      '#default_value' => $config->get('sw_lat_0'),
    ];
    $form['sw_lon_0'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campus Southwest Longitude'),
      '#description' => $this->t('Set the Southwest Longitude coordinates. Empty for default.'),
      '#default_value' => $config->get('sw_lon_0'),
    ];

    /* Uncomment to add more campuses
    $form['overlay_path_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('North Campus Overlay path'),
      '#description' => $this->t('Set relative or absolute path to custom overlay. Tokens supported. Empty for default.'),
      '#default_value' => $config->get('overlay_path_1'),
    ];

    $form['ne_lat_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('North Campus Northeast Latitude'),
      '#description' => $this->t('Set the Northeast Latitude coordinates. Empty for default.'),
      '#default_value' => $config->get('ne_lat_1'),
    ];
    $form['ne_lon_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('North Campus Northeast Longitude'),
      '#description' => $this->t('Set the Northeast Longitude coordinates. Empty for default.'),
      '#default_value' => $config->get('ne_lon_1'),
    ];
    $form['sw_lat_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('North Campus Southwest Latitude'),
      '#description' => $this->t('Set the Southwest Latitude coordinates. Empty for default.'),
      '#default_value' => $config->get('sw_lat_1'),
    ];
    $form['sw_lon_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('North Campus Southwest Longitude'),
      '#description' => $this->t('Set the Southwest Longitude coordinates. Empty for default.'),
      '#default_value' => $config->get('sw_lon_1'),
    ];




    $form['overlay_path_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('South Campus Overlay path'),
      '#description' => $this->t('Set relative or absolute path to custom overlay. Tokens supported. Empty for default.'),
      '#default_value' => $config->get('overlay_path_2'),
    ];

    $form['ne_lat_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('South Campus Northeast Latitude'),
      '#description' => $this->t('Set the Northeast Latitude coordinates. Empty for default.'),
      '#default_value' => $config->get('ne_lat_2'),
    ];
    $form['ne_lon_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('South Campus Northeast Longitude'),
      '#description' => $this->t('Set the Northeast Longitude coordinates. Empty for default.'),
      '#default_value' => $config->get('ne_lon_2'),
    ];
    $form['sw_lat_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('South Campus Southwest Latitude'),
      '#description' => $this->t('Set the Southwest Latitude coordinates. Empty for default.'),
      '#default_value' => $config->get('sw_lat_2'),
    ];
    $form['sw_lon_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('South Campus Southwest Longitude'),
      '#description' => $this->t('Set the Southwest Longitude coordinates. Empty for default.'),
      '#default_value' => $config->get('sw_lon_2'),
    ]; */

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
    $this->config('kwall_map.settings')
      ->set('style', $form_state->getValue('style'))
      ->set('overlay_path_0', $form_state->getValue('overlay_path_0'))
      ->set('sw_lat_0', $form_state->getValue('sw_lat_0'))
      ->set('sw_lon_0', $form_state->getValue('sw_lon_0'))
      ->set('ne_lat_0', $form_state->getValue('ne_lat_0'))
      ->set('ne_lon_0', $form_state->getValue('ne_lon_0'))
      /* ->set('overlay_path_1', $form_state->getValue('overlay_path_1'))
      ->set('sw_lat_1', $form_state->getValue('sw_lat_1'))
      ->set('sw_lon_1', $form_state->getValue('sw_lon_1'))
      ->set('ne_lat_1', $form_state->getValue('ne_lat_1'))
      ->set('ne_lon_1', $form_state->getValue('ne_lon_1'))
      ->set('overlay_path_2', $form_state->getValue('overlay_path_2'))
      ->set('sw_lat_2', $form_state->getValue('sw_lat_2'))
      ->set('sw_lon_2', $form_state->getValue('sw_lon_2'))
      ->set('ne_lat_2', $form_state->getValue('ne_lat_2'))
      ->set('ne_lon_2', $form_state->getValue('ne_lon_2')) */
      ->save();
    parent::submitForm($form, $form_state);
  }

}