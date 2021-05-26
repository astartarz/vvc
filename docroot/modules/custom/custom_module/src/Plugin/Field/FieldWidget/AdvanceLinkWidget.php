<?php

namespace Drupal\custom_module\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "link_advance",
 *   label = @Translation("Advance Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class AdvanceLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['options'] = [
      '#type' => 'fieldgroup',
    ];

    $element['options']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class'),
      '#placeholder' => $this->t('Anchor tag class'),
      '#description' => $this->t('Add a custom class separated by a space. Default styling is yellow. Available default classes - blue | transparent'),
      '#default_value' => !empty($items[$delta]->options['class']) ? $items[$delta]->options['class'] : '',
      '#maxlength' => 255,
    ];

    $element['options']['data_video_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data Video Link'),
      '#placeholder' => $this->t('https://www.youtube.com/embed/NpEaa2P7qZI'),
      '#default_value' => !empty($items[$delta]->options['data_video_link']) ? $items[$delta]->options['data_video_link'] : '',
      '#description' => $this->t('URL must be embed link. Youtube Example: https://www.youtube.com/embed/NpEaa2P7qZI  | Vimeo Example: https://player.vimeo.com/video/87110435 '),
      '#element_validate' => [[get_called_class(), 'validateVideoUrl']],
      '#maxlength' => 255,
    ];

    $element['options']['aria_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Aria Label'),
      '#placeholder' => $this->t('Anchor tag attribute aria-label'),
      '#default_value' => !empty($items[$delta]->options['aria_label']) ? $items[$delta]->options['aria_label'] : '',
      '#maxlength' => 255,
    ];

    return $element;
  }

  /**
   * Validate url of video.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form
   */
  public static function validateVideoUrl($element, FormStateInterface $form_state, $form) {
    if (!empty($element['#value'])) {
      if (!filter_var($element['#value'], FILTER_VALIDATE_URL)) {
        $form_state->setError($element, t('Please use valid video url.'));
        return;
      }
    }
  }
}
