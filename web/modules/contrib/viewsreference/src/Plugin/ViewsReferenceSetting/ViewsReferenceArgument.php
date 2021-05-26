<?php

namespace Drupal\viewsreference\Plugin\ViewsReferenceSetting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting argument plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "argument",
 *   label = @Translation("Argument"),
 *   default_value = "",
 * )
 */
class ViewsReferenceArgument extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#weight'] = 40;
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value, EntityInterface $entity) {
    if (!empty($value)) {
      $arguments = [$value];
      if (preg_match('/\//', $value)) {
        $arguments = explode('/', $value);
      }

      $replacements = [];
      $replacements[$entity->getEntityTypeId()] = $entity;
      if (!array_key_exists('node', $replacements)) {
        $node = \Drupal::routeMatch()->getParameter('node');
        $replacements['node'] = $node;
      }

      $token_service = \Drupal::token();
      if (is_array($arguments)) {
        foreach ($arguments as $index => $argument) {
          if (!empty($token_service->scan($argument))) {
            $arguments[$index] = $token_service->replace($argument, $replacements);
          }
        }
      }

      $view->setArguments($arguments);
    }
  }

}
