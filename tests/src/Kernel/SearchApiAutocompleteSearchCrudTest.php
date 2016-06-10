<?php

namespace Drupal\Tests\search_api_autocomplete\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;

class SearchApiAutocompleteSearchCrudTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['search_api_autocomplete', 'search_api', 'user'];

  public function testCreate() {
    $autocomplete_search = SearchApiAutocompleteSearch::create([
      'id' => 'muh',
      'label' => 'Meh',
      'index_id' => 'index1',
      'suggester_id' => 'suggstester1',
      'type' => 'test_type',
      'options' => ['key' => 'value'],
    ]);
    $autocomplete_search->save();
  }

}
