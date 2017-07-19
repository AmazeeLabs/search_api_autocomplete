<?php

namespace Drupal\search_api_autocomplete_test\Plugin\search_api_autocomplete\type;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Type\TypePluginBase;
use Drupal\search_api_test\TestPluginTrait;

/**
 * Defines a test type class.
 *
 * @SearchApiAutocompleteType(
 *   id = "search_api_autocomplete_test",
 *   label = @Translation("Test type"),
 *   description = @Translation("Type used for tests."),
 * )
 */
class TestType extends TypePluginBase implements PluginFormInterface {

  use TestPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this, $form, $form_state);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $form, $form_state);
      return;
    }
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function listSearches(IndexInterface $index) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this, $index);
    }
    return [
      'search_api_autocomplete_test' => [
        'label' => 'Autocomplete test module search',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery(SearchInterface $search, $keys) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this, $search, $keys);
    }
    return $search->getIndex()->query()->keys($keys);
  }
}
