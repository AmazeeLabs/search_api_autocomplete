<?php

namespace Drupal\Tests\search_api_autocomplete\FunctionalJavascript;

use Behat\Mink\Driver\GoutteDriver;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\search_api_autocomplete\Tests\TestsHelper;
use Drupal\search_api_test\PluginTestTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the functionality of the whole module from a user's perspective.
 *
 * @group search_api_autocomplete
 */
class IntegrationTest extends JavascriptTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api_test',
    'search_api_autocomplete_test',
  ];

  /**
   * The ID of the search index used in this test.
   *
   * @var string
   */
  protected $indexId = 'autocomplete_search_index';

  /**
   * The ID of the search entity created for this test.
   *
   * @var string
   */
  protected $searchId = 'search_api_autocomplete_test_view';

  /**
   * An admin user used for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A normal (non-admin) user used for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'administer search_api',
      'administer search_api_autocomplete',
      'administer permissions',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);

    $this->normalUser = $this->drupalCreateUser();
  }

  /**
   * Tests the complete functionality of the module via the UI.
   */
  public function testModule() {
    $this->drupalLogin($this->adminUser);

    $this->enableSearch();
    $this->configureSearch();
    $this->checkSearchAutocomplete();
    $this->checkAutocompleteAccess();
    $this->checkAdminAccess();
  }

  /**
   * Goes to the index's "Autocomplete" tab and creates/enables the test search.
   */
  protected function enableSearch() {
    $assert_session = $this->assertSession();

    // Make test suggester incompatible with all searches to test the "No
    // suggesters available" message.
    $callback = [TestsHelper::class, 'returnFalse'];
    $this->setMethodOverride('suggester', 'supportsSearch', $callback);

    $this->drupalGet($this->getAdminPath());
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('There are currently no suggester plugins installed that support this index.');

    // Make it compatible with all searches again and refresh page.
    $this->setMethodOverride('suggester', 'supportsSearch', NULL);

    $this->getSession()->reload();
    $this->logPageChange();
    $assert_session->statusCodeEquals(200);

    // Check whether all expected types and searches are present.
    $assert_session->pageTextContains('Search views');
    $assert_session->pageTextContains('Searches provided by Views');
    $assert_session->pageTextContains('Search API Autocomplete Test view');
    $assert_session->pageTextContains('Test type');
    $assert_session->pageTextContains('Autocomplete test module search');

    // Enable all Views searches (just one).
    $assert_session->checkboxNotChecked("searches[{$this->searchId}]");
    $this->click('table[data-drupal-selector="edit-search-views-searches"] > thead > tr > th.select-all input.form-checkbox');
    $assert_session->checkboxChecked("searches[{$this->searchId}]");

    $this->click('[data-drupal-selector="edit-submit"]');
    $this->logPageChange(NULL, 'POST');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('The settings have been saved. Please remember to set the permissions for the newly enabled searches.');
  }

  /**
   * Configures the test search via the UI.
   */
  protected function configureSearch() {
    $assert_session = $this->assertSession();

    $this->click('.dropbutton-action a[href$="/edit"]');
    $this->logPageChange();
    $assert_session->statusCodeEquals(200);
    $assert_session->addressEquals($this->getAdminPath('edit'));

    // The "Server" suggester shouldn't be available at that point.
    $assert_session->elementExists('css', 'input[name="suggesters[enabled][search_api_autocomplete_test]"]');
    $assert_session->elementNotExists('css', 'input[name="suggesters[enabled][server]"]');

    // Make the test backend support autocomplete so that the "Server" suggester
    // becomes available.
    $callback = [TestsHelper::class, 'getSupportedFeatures'];
    $this->setMethodOverride('backend', 'getSupportedFeatures', $callback);
    $callback = [TestsHelper::class, 'getAutocompleteSuggestions'];
    $this->setMethodOverride('backend', 'getAutocompleteSuggestions', $callback);

    // After refreshing, the "Server" suggester should now be available. But by
    // default, only our test suggester should be enabled (since it was the only
    // option before).
    $this->getSession()->reload();
    $this->logPageChange();
    $assert_session->checkboxChecked('suggesters[enabled][search_api_autocomplete_test]');
    $assert_session->checkboxNotChecked('suggesters[enabled][server]');

    // The "Server" suggester's config form is hidden by default, but displayed
    // once we check its "Enabled" checkbox.
    $this->assertNotVisible('css', 'details[data-drupal-selector="edit-suggesters-settings-server"]');
    $this->click('input[name="suggesters[enabled][server]"]');
    $this->assertVisible('css', 'details[data-drupal-selector="edit-suggesters-settings-server"]');

    // Submit the form with some values for all fields.
    $edit = [
      'suggesters[weights][search_api_autocomplete_test][limit]' => '3',
      'suggesters[weights][server][limit]' => '3',
      'suggesters[weights][search_api_autocomplete_test][weight]' => '0',
      'suggesters[weights][server][weight]' => '10',
      'suggesters[settings][server][fields][name]' => FALSE,
      'suggesters[settings][server][fields][body]' => TRUE,
      'type_settings[displays][selected][default]' => FALSE,
      'options[limit]' => '5',
      'options[min_length]' => '2',
      'options[show_count]' => TRUE,
      'options[delay]' => '1000',
    ];
    $this->submitForm($edit, 'Save');
  }

  /**
   * Tests autocompletion in the search form.
   */
  protected function checkSearchAutocomplete() {
    $assert_session = $this->assertSession();

    $this->drupalGet('search-api-autocomplete-test');
    $assert_session->statusCodeEquals(200);

    $assert_session->elementAttributeContains('css', 'input[data-drupal-selector="edit-keys"]', 'data-search-api-autocomplete-search', $this->searchId);

    $elements = $this->getAutocompleteSuggestions();
    $suggestions = [];
    $suggestion_elements = [];
    foreach ($elements as $element) {
      $user_input = $element->find('css', '.autocomplete-suggestion-user-input')
        ->getText();
      $suffix = $element->find('css', '.autocomplete-suggestion-suggestion-suffix')
        ->getText();
      $count = $element->find('css', '.autocomplete-suggestion-results');
      $keys = $user_input . $suffix;
      $suggestions[] = [
        'keys' => $keys,
        'count' => $count ? $count->getText() : NULL,
      ];
      $suggestion_elements[$keys] = $element;
    }
    $expected = [
      [
        'keys' => 'test-suggester-1',
        'count' => 1,
      ],
      [
        'keys' => 'test-suggester-2',
        'count' => 2,
      ],
      [
        'keys' => 'test-suggester-3',
        'count' => 3,
      ],
      [
        'keys' => 'test-backend-1',
        'count' => 1,
      ],
      [
        'keys' => 'test-backend-2',
        'count' => 2,
      ],
    ];
    $this->assertEquals($expected, $suggestions);

    /** @var \Drupal\search_api\Query\QueryInterface $query */
    list($query) = $this->getMethodArguments('backend', 'getAutocompleteSuggestions');
    $this->assertEquals(['body'], $query->getFulltextFields());

    // Click one of the suggestions. The form should now auto-submit.
    $suggestion_elements['test-suggester-1']->click();
    $this->logPageChange();
    $assert_session->addressEquals('/search-api-autocomplete-test');
    $this->assertRegExp('#[?&]keys=test-suggester-1#', $this->getUrl());

    // Check that autocomplete in the "Name" filter works, too, and that it sets
    // the correct fields on the query.
    $this->getAutocompleteSuggestions('edit-name-value');
    list($query) = $this->getMethodArguments('suggester', 'getAutocompleteSuggestions');
    $this->assertEquals(['name'], $query->getFulltextFields());
  }

  /**
   * Retrieves autocomplete suggestions from a field on the current page.
   *
   * @param string $field_html_id
   *   (optional) The HTML ID of the field.
   * @param string $input
   *   (optional) The input to write into the field.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The suggestion elements from the page.
   */
  protected function getAutocompleteSuggestions($field_html_id = 'edit-keys', $input = 'test') {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $field = $assert_session->elementExists('css', "input[data-drupal-selector=\"$field_html_id\"]");
    $field->setValue(substr($input, 0, -1));
    $this->getSession()->getDriver()->keyDown($field->getXpath(), substr($input, -1));

    $element = $assert_session->waitOnAutocomplete();
    $this->assertTrue($element && $element->isVisible());
    $this->logPageChange();

    $locator = '.ui-autocomplete .search-api-autocomplete-suggestion';
    // Contrary to documentation, this can also return NULL. Therefore, we need
    // to make sure to return an array even in this case.
    return $page->findAll('css', $locator) ?: [];
  }

  /**
   * Verifies that autocomplete is only applied after access checks.
   */
  protected function checkAutocompleteAccess() {
    $assert_session = $this->assertSession();

    // Make sure autocomplete functionality is only available for users with the
    // right permission.
    $users = [
      'non-admin' => $this->normalUser,
      'anonymous' => NULL,
    ];
    $permission = "use search_api_autocomplete for {$this->searchId}";
    $autocomplete_path = "search_api_autocomplete/{$this->searchId}";
    foreach ($users as $user_type => $account) {
      $this->drupalLogout();
      if ($account) {
        $this->drupalLogin($account);
      }

      $this->drupalGet('search-api-autocomplete-test');
      $assert_session->statusCodeEquals(200);
      $element = $assert_session->elementExists('css', 'input[data-drupal-selector="edit-keys"]');
      $this->assertFalse($element->hasAttribute('data-search-api-autocomplete-search'), "Autocomplete should not be enabled for $user_type user without the necessary permission.");
      $this->assertFalse($element->hasClass('form-autocomplete'), "Autocomplete should not be enabled for $user_type user without the necessary permission.");

      $this->drupalGet($autocomplete_path, ['query' => ['q' => 'test']]);
      $assert_session->statusCodeEquals(403);

      $rid = $account ? 'authenticated' : 'anonymous';
      Role::load($rid)->grantPermission($permission)->save();

      $this->drupalGet('search-api-autocomplete-test');
      $assert_session->statusCodeEquals(200);
      $element = $assert_session->elementExists('css', 'input[data-drupal-selector="edit-keys"]');
      $this->assertTrue($element->hasAttribute('data-search-api-autocomplete-search'), "Autocomplete should not be enabled for $user_type user without the necessary permission.");
      $this->assertContains($this->searchId, $element->getAttribute('data-search-api-autocomplete-search'), "Autocomplete should not be enabled for $user_type user without the necessary permission.");
      $this->assertTrue($element->hasClass('form-autocomplete'), "Autocomplete should not be enabled for $user_type user without the necessary permission.");

      $this->drupalGet($autocomplete_path, ['query' => ['q' => 'test']]);
      $assert_session->statusCodeEquals(200);
    }
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Verifies that admin pages are properly protected.
   */
  protected function checkAdminAccess() {
    // Make sure anonymous and non-admin users cannot access admin pages.
    $users = [
      'non-admin' => $this->normalUser,
      'anonymous' => NULL,
    ];
    $paths = [
      'index overview' => $this->getAdminPath(),
      'search edit form' => $this->getAdminPath('edit'),
      'search delete form' => $this->getAdminPath('delete'),
    ];
    foreach ($users as $user_type => $account) {
      $this->drupalLogout();
      if ($account) {
        $this->drupalLogin($account);
      }
      foreach ($paths as $label => $path) {
        $this->drupalGet($path);
        $status_code = $this->getSession()->getStatusCode();
        $this->assertEquals(403, $status_code, "The $label is accessible for $user_type users.");
      }
    }
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Returns the path of an admin page.
   *
   * @param string|null $page
   *   (optional) "edit" or "delete" to get the path of the respective search
   *   form, or NULL for the index's "Autocomplete" tab.
   * @param string|null $search_id
   *   (optional) The ID of the search to link to, if a page is specified. NULL
   *   to use the default search used by this test.
   *
   * @return string
   */
  protected function getAdminPath($page = NULL, $search_id = NULL) {
    $path = 'admin/config/search/search-api/index/autocomplete_search_index/autocomplete';
    if ($page !== NULL) {
      if ($search_id === NULL) {
        $search_id = $this->searchId;
      }
      $path .= "/$search_id/$page";
    }
    return $path;
  }

  /**
   * Logs a page change, if HTML output logging is enabled.
   *
   * The base class only logs requests when the drupalGet() or drupalPost()
   * methods are used, so we need to implement this ourselves for other page
   * changes.
   *
   * To enable HTML output logging, create some file where links to the logged
   * pages should be placed and set the "BROWSERTEST_OUTPUT_FILE" environment
   * variable to that file's path.
   *
   * @param string|null $url
   *   (optional) The URL requested, if not the current URL.
   * @param string $method
   *   (optional) The HTTP method used for the request.
   *
   * @see \Drupal\Tests\BrowserTestBase::drupalGet()
   * @see \Drupal\Tests\BrowserTestBase::setUp()
   */
  protected function logPageChange($url = NULL, $method = 'GET') {
    $session = $this->getSession();
    $driver = $session->getDriver();
    if (!$this->htmlOutputEnabled || $driver instanceof GoutteDriver) {
      return;
    }
    $current_url = $session->getCurrentUrl();
    $url = $url ?: $current_url;
    $html_output = "$method request to: $url<hr />Ending URL: $current_url";
    $html_output .= '<hr />' . $session->getPage()->getContent();;
    $html_output .= $this->getHtmlOutputHeaders();
    $this->htmlOutput($html_output);
  }

  /**
   * Asserts that the specified element exists and is visible.
   *
   * @param string $selector_type
   *   The element selector type (CSS, XPath).
   * @param string|array $selector
   *   The element selector. Note: the first found element is used.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   *   Thrown if the element doesn't exist.
   */
  protected function assertVisible($selector_type, $selector) {
    $element = $this->assertSession()->elementExists($selector_type, $selector);
    $this->assertTrue($element->isVisible(), "Element should be visible but isn't.");
  }

  /**
   * Asserts that the specified element exists but is not visible.
   *
   * @param string $selector_type
   *   The element selector type (CSS, XPath).
   * @param string|array $selector
   *   The element selector. Note: the first found element is used.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   *   Thrown if the element doesn't exist.
   */
  protected function assertNotVisible($selector_type, $selector) {
    $element = $this->assertSession()->elementExists($selector_type, $selector);
    $this->assertFalse($element->isVisible(), "Element shouldn't be visible but is.");
  }

}
