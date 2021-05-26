<?php

declare(strict_types=1);

namespace Drupal\twig_remove_html_comments\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig filter that removes HTML comments from input.
 *
 * @package Drupal\twig_remove_html_comments\TwigExtension
 */
class RemoveHtmlComments extends AbstractExtension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   *
   * @return array
   *   The Twig filters of this extension.
   */
  public function getFilters(): array {
    return [
      new TwigFilter('remove_html_comments', [$this, 'removeHtmlCommentsAsRenderArray']),
      new TwigFilter('remove_html_comments_as_string', [$this, 'removeHtmlCommentsFromString']),
    ];
  }

  /**
   * Gets the unique identifier for this Twig extension.
   *
   * @return string
   *   The identifier for this extension.
   */
  public function getName(): string {
    return 'twig_remove_html_comments.remove_html_comments';
  }

  /**
   * Removes HTML comments from string.
   *
   * Example: A string which has value <!--Start DEBUG--> ABCD <!--End DEBUG-->
   * will be returned the output ABCD after using the the following function.
   *
   * @param string|null $string
   *   A string or NULL.
   *
   * @return array
   *   The string as markup render array without HTML comments.
   */
  public function removeHtmlCommentsAsRenderArray(?string $string): array {
    if ($string !== NULL) {
      $stringWithoutHtmlComments = $this->removeHtmlCommentsFromString($string);
    }
    else {
      $stringWithoutHtmlComments = '';
    }

    return [
      '#markup' => $stringWithoutHtmlComments,
    ];
  }

  /**
   * Removes HTML comments from string.
   *
   * Example: A string which has value <!--Start DEBUG--> ABCD <!--End DEBUG-->
   * will be returned the output ABCD after using the the following function.
   *
   * @param string|null $string
   *   A string or NULL.
   *
   * @return string
   *   The string without HTML comments.
   */
  public function removeHtmlCommentsFromString(?string $string): string {
    if ($string !== NULL) {
      return preg_replace('/<!--(.|\s)*?-->\s*|\r|\n/', '', $string);
    }
    else {
      return '';
    }
  }

}
