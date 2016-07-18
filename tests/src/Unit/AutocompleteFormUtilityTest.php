<?php

namespace Drupal\Tests\search_api_autocomplete\Unit;

use Drupal\search_api_autocomplete\AutocompleteFormUtility;

/**
 * @coversDefaultClass \Drupal\search_api_autocomplete\AutocompleteFormUtility
 * @group search_api_autocomplete
 */
class AutocompleteFormUtilityTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::splitKeys
   *
   * @dataProvider providerTestSplitKeys
   */
  public function testSplitKeys($keys, array $expected) {
    $this->assertEquals($expected, AutocompleteFormUtility::splitKeys($keys));
  }

  /**
   * Data provider for testSplitKeys().
   */
  public function providerTestSplitKeys() {
    $data = [];
    $data['simple-word'] = ['word', ['', 'word']];
    $data['simple-word-dash'] = ['word-dash', ['', 'word-dash']];
    $data['whitespace-right-side'] = ['word-dash ', ['word-dash', '']];
    $data['quote-word-start'] = ['"word" other', ['"word"', 'other']];
    $data['quote-word-end'] = ['word "other"', ['word "other"', '']];

    return $data;
  }

}
