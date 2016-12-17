<?php

namespace Drupal\search_api_autocomplete\Plugin\search_api_autocomplete\AutocompleteType;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\QueryHelper;
use Drupal\search_api_autocomplete\Type\TypeInterface;
use Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a autocompletion type for the search_api_page module.
 *
 * @SearchapiAutocompleteType(
 *   id = "page",
 *   label = @Translation("Search pages"),
 *   description = @Translation("Searches provided by the <em>Search pages</em> module."),
 *   provider = "search_api_page",
 * )
 */
class Page extends PluginBase implements TypeInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The query helper service.
   *
   * @var \Drupal\search_api\Utility\QueryHelper|null
   */
  protected $queryHelper;

  /**
   * Creates a new Page instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );

    $plugin->setQueryHelper($container->get('search_api.query_helper'));

    return $plugin;
  }

  /**
   * Retrieves the query helper.
   *
   * @return \Drupal\search_api\Utility\QueryHelper
   *   The query helper.
   */
  public function getQueryHelper() {
    return $this->queryHelper ?: \Drupal::service('search_api.query_helper');
  }

  /**
   * Sets the query helper.
   *
   * @param \Drupal\search_api\Utility\QueryHelper $query_helper
   *   The new query helper.
   *
   * @return $this
   */
  public function setQueryHelper(QueryHelper $query_helper) {
    $this->queryHelper = $query_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function listSearches(IndexInterface $index) {
    $ret = [];
    $storage = $this->entityTypeManager->getStorage('search_api_page');
    foreach ($storage->loadByProperties(['index' => $index->id()]) as $page) {
      $id = 'search_api_page_' . $page->id();
      $ret[$id]['name'] = $page->label();
      $ret[$id]['options']['custom']['page_id'] = $page->id();
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery(SearchApiAutocompleteSearchInterface $search, $complete, $incomplete) {
    /** @var \Drupal\search_api_page\SearchApiPageInterface $page */
    $page = $this->entityTypeManager
      ->getStorage('search_api_page')
      ->load($search->getOption('custom.page_id'));
    $index = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->load($page->getIndex());
    $query = $this->getQueryHelper()->createQuery($index);
    $query->keys($complete);
    if ($page->getFulltextFields()) {
      $query->setFulltextFields($page->getSearchedFields());
    }
    return $query;
  }

}
