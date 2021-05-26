<?php

namespace Drupal\Tests\twig_remove_html_comments\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\twig_remove_html_comments\TwigExtension\RemoveHtmlComments;
use Twig\TwigFilter;

/**
 * Tests the RemoveHtmlComments class.
 *
 * @package Drupal\Tests\twig_remove_html_comments\Unit
 * @coversDefaultClass \Drupal\twig_remove_html_comments\TwigExtension\RemoveHtmlComments
 * @group twig_remove_html_comments
 */
class RemoveHtmlCommentsTest extends UnitTestCase {

  /**
   * The object to test.
   *
   * @var \Drupal\twig_remove_html_comments\TwigExtension\RemoveHtmlComments
   */
  protected $removeHtmlComments;

  /**
   * Sets up to object to test.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->removeHtmlComments = new RemoveHtmlComments();
  }

  /**
   * @covers ::getName
   */
  public function testGetName(): void {
    $this->assertTrue(!empty($this->removeHtmlComments->getName()));
  }

  /**
   * @covers ::getFilters
   */
  public function testGetFilters(): void {
    $filters = $this->removeHtmlComments->getFilters();

    $this->assertTrue(is_array($filters));

    foreach ($filters as $filter) {
      $this->assertTrue($filter instanceof TwigFilter);
    }
  }

  /**
   * @covers ::removeHtmlCommentsFromString
   */
  public function testRemoveHtmlCommentsFromString(): void {
    $stringWithSingleComment = '<!-- This is a HTML comment --><p>HTML p tag</p><h1>HTML 1 tag</h1>';
    $this->assertEquals(
      '<p>HTML p tag</p><h1>HTML 1 tag</h1>',
      $this->removeHtmlComments->removeHtmlCommentsFromString($stringWithSingleComment)
    );

    $stringWithMultipleComments = '<!-- This is a HTML comment --><p>HTML p tag</p><h1>HTML 1 tag</h1><!-- This is another HTML comment -->';
    $this->assertEquals(
      '<p>HTML p tag</p><h1>HTML 1 tag</h1>',
      $this->removeHtmlComments->removeHtmlCommentsFromString($stringWithMultipleComments)
    );

    $stringWithoutComments = '<span>This is a normal string without any HTML comments</span>';
    $this->assertEquals(
      '<span>This is a normal string without any HTML comments</span>',
      $this->removeHtmlComments->removeHtmlCommentsFromString($stringWithoutComments)
    );

    $stringWithoutHTML = 'This is a string without any HTML';
    $this->assertEquals(
      'This is a string without any HTML',
      $this->removeHtmlComments->removeHtmlCommentsFromString($stringWithoutHTML)
    );

    $this->assertEquals(
      '',
      $this->removeHtmlComments->removeHtmlCommentsFromString(NULL)
    );
  }

  /**
   * @covers ::removeHtmlCommentsAsRenderArray
   */
  public function testRemoveHtmlCommentsAsRenderArray(): void {
    $stringWithSingleComment = '<!-- This is a HTML comment --><p>HTML p tag</p><h1>HTML 1 tag</h1>';
    $this->assertEquals(
      ['#markup' => '<p>HTML p tag</p><h1>HTML 1 tag</h1>'],
      $this->removeHtmlComments->removeHtmlCommentsAsRenderArray($stringWithSingleComment)
    );

    $stringWithMultipleComments = '<!-- This is a HTML comment --><p>HTML p tag</p><h1>HTML 1 tag</h1><!-- This is another HTML comment -->';
    $this->assertEquals(
      ['#markup' => '<p>HTML p tag</p><h1>HTML 1 tag</h1>'],
      $this->removeHtmlComments->removeHtmlCommentsAsRenderArray($stringWithMultipleComments)
    );

    $stringWithoutComments = '<span>This is a normal string without any HTML comments</span>';
    $this->assertEquals(
      ['#markup' => '<span>This is a normal string without any HTML comments</span>'],
      $this->removeHtmlComments->removeHtmlCommentsAsRenderArray($stringWithoutComments)
    );

    $simpleStringWithoutHTML = 'This is a string without any HTML';
    $this->assertEquals(
      ['#markup' => 'This is a string without any HTML'],
      $this->removeHtmlComments->removeHtmlCommentsAsRenderArray($simpleStringWithoutHTML)
    );

    $this->assertEquals(
      ['#markup' => ''],
      $this->removeHtmlComments->removeHtmlCommentsAsRenderArray(NULL)
    );
  }

}
