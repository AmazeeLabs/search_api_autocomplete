<?php

namespace Drupal\Tests\search_api_autocomplete\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Query\ConditionInterface;

/**
 * Tests Views integration of the Autocomplete module.
 *
 * @group search_api_autocomplete
 */
class ViewsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'search_api_autocomplete',
    'search_api_autocomplete_test',
    'search_api',
    'search_api_test',
    'search_api_test_example_content',
    'system',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installConfig('search_api');
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('user');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (php_sapi_name() != 'cli') {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }
  }

  /**
   * Tests that valid search plugin definitions are created for search views.
   */
  public function testSearchPlugin() {
    $index_id = 'autocomplete_search_index';
    $plugin_helper = $this->container
      ->get('search_api_autocomplete.plugin_helper');

    $plugins = $plugin_helper->createSearchPluginsForIndex($index_id);
    $view_id = 'views:search_api_autocomplete_test_view';
    $this->assertArrayNotHasKey($view_id, $plugins);

    $this->installConfig('search_api_autocomplete_test');

    // Unfortunately, we need to rebuild the container here (or, at least the
    // search plugin manager, but this is the simplest way), because otherwise
    // the deriver's internal static cache will ruin the test.
    /** @var \Drupal\Core\DrupalKernelInterface $kernel */
    $kernel = $this->container->get('kernel');
    $kernel->rebuildContainer();
    $plugin_helper = $this->container
      ->get('search_api_autocomplete.plugin_helper');

    $plugins = $plugin_helper->createSearchPluginsForIndex($index_id);
    $this->assertArrayHasKey($view_id, $plugins);
    /** @var \Drupal\search_api_autocomplete\Search\SearchPluginInterface $plugin */
    $plugin = $plugins[$view_id];
    $this->assertEquals('Search API Autocomplete Test view', $plugin->label());
    $this->assertEquals('Search views', $plugin->getGroupLabel());
    $this->assertEquals('Searches provided by Views', $plugin->getGroupDescription());

    $data = [
      'display' => 'page',
      'filter' => 'keys',
    ];
    $query = $plugin->createQuery('foobar', $data);
    $this->assertEquals('foobar', $query->getOriginalKeys());
    $index = $query->getIndex();
    $this->assertEquals($index_id, $index->id());
    $fields = $query->getFulltextFields();
    $all_fulltext_fields = $index->getFulltextFields();
    $fields = isset($fields) ? $fields : $all_fulltext_fields;
    $this->assertEquals($all_fulltext_fields, $fields);

    $query = $plugin->createQuery('', $data);
    $this->assertNull($query->getOriginalKeys());

    $data = [
      'display' => 'page',
      'filter' => 'name',
      'field' => 'name',
    ];
    $query = $plugin->createQuery('foobar', $data);
    $this->assertNull($query->getOriginalKeys());
    $this->assertContains('foobar', (string) $query);
    $conditions = $query->getConditionGroup()->getConditions();
    $conditions = $this->collectConditions($conditions);
    $this->assertCount(1, $conditions);
    $condition = $conditions[0];
    $this->assertEquals('name', $condition->getField());
    $this->assertEquals('foobar', $condition->getValue());
    $this->assertEquals($index_id, $query->getIndex()->id());
    $this->assertEquals(['name'], $query->getFulltextFields());
  }

  /**
   * Collects conditions from an array of conditions and condition groups.
   *
   * Any information about condition nesting, group operators, etc. is lost.
   * Only makes sense for tests.
   *
   * @param \Drupal\search_api\Query\ConditionInterface[]|\Drupal\search_api\Query\ConditionGroupInterface[] $conditions
   *   An array of conditions and condition groups.
   *
   * @return \Drupal\search_api\Query\ConditionInterface[]
   *   All conditions contained in the given array of conditions and condition
   *   groups.
   */
  protected function collectConditions(array $conditions) {
    $ret = [];

    foreach ($conditions as $condition) {
      if ($condition instanceof ConditionInterface) {
        $ret[] = $condition;
      }
      else {
        $new = $this->collectConditions($condition->getConditions());
        $ret = array_merge($ret, $new);
      }
    }

    return $ret;
  }

}
