<?php

namespace Drupal\search_api_autocomplete\Plugin\search_api_autocomplete\AutocompleteType;

use Drupal\Core\Plugin\PluginBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility;
use Drupal\search_api_autocomplete\AutocompleteTypeInterface;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;
use Drupal\search_api_page\Entity\SearchApiPage;

/**
 * @AutocompleteType(
 *   id = "page",
 *   label = @Translation("Search pages"),
 *   description = @Translation("Searches provided by the <em>Search pages</em> module."),
 *   provider = "search_api_page",
 * )
 */
class Page extends PluginBase implements AutocompleteTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function listSearches(IndexInterface $index) {
    $ret = array();
    $storage = \Drupal::entityTypeManager()->getStorage('search_api_page');
    foreach ($storage->loadByProperties(['index_id' => $index->id()]) as $page) {
      $id = 'search_api_page_' . $page->id();
      $ret[$id]['name'] = $page->label();
      $ret[$id]['options']['custom']['page_id'] = $page->id();
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery(SearchApiAutocompleteSearch $search, $complete, $incomplete) {
    $page = SearchApiPage::load($search->getOption('custom.page_id'));
    // Copied from search_api_page_search_execute().
    $query = Utility::createQuery(Index::load($page->getIndex()));
    $query
      ->keys($complete);
    if ($page->getFulltextFields()) {
      $query->setFulltextFields($page->getFulltextFields());
    }
    return $query;
  }

}
