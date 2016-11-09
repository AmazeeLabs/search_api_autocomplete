<?php

namespace Drupal\search_api_autocomplete\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\Type\TypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an edit for for autocomplete search entities.
 *
 * @see \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface
 */
class AutocompleteSearchEditForm extends EntityForm {

  /**
   * The entity.
   *
   * @var \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface
   */
  protected $entity;

  /**
   * The autocomplete suggester manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $suggesterManager;

  /**
   * The autocomplete type manager.
   *
   * @var \Drupal\search_api_autocomplete\Type\TypeManager
   */
  protected $typeManager;

  /**
   * Creates a new AutocompleteSearchEditForm instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $suggester_manager
   *   The suggester manager.
   * @param \Drupal\search_api_autocomplete\Type\TypeManager $autocomplete_type_manager
   *   The autocomplete type manager.
   */
  public function __construct(PluginManagerInterface $suggester_manager, TypeManager $autocomplete_type_manager) {
    $this->suggesterManager = $suggester_manager;
    $this->typeManager = $autocomplete_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin_manager.search_api_autocomplete_suggester'),
      $container->get('plugin_manager.search_api_autocomplete_type')
    );
  }

  /**
   * Returns all suggesters matching for a particular search index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index to filter by
   *
   * @return \Drupal\search_api_autocomplete\Suggester\SuggesterInterface[]
   */
  protected function getSuggestersForIndex(IndexInterface $index) {
    $suggesters = array_map(function ($suggester_info) {
      return $suggester_info['class'];
    }, $this->suggesterManager->getDefinitions());
    $suggesters = array_filter($suggesters, function ($suggester_class) use ($index) {
      return $suggester_class::supportsIndex($index);
    });
    return $suggesters;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $search = $search_api_autocomplete_search = $this->entity;
    $form['#title'] = $this->t('Configure autocompletion for %search', ['%search' => $search_api_autocomplete_search->label()]);

    // If this is a re-build (i.e., most likely an AJAX call due to a new
    // suggester being selected), prepare the suggester sub-form state
    // accordingly.
    $selected_suggester_id = $search->getSuggesterId();
    if (!empty($form_state->getValue('suggester_id'))) {
      $selected_suggester_id = $form_state->getValue('suggester_id');
      // Don't let submitted values for a different suggester influence another
      // suggester's form.
      if ($selected_suggester_id != $form_state->getValue('old_suggester_id')) {
        $form_state->setValue(['options', 'suggester_configuration'], NULL);
        $form_state->set(['options', 'suggester_configuration'], NULL);
      }
    }

    /** @var \Drupal\search_api_autocomplete\Type\TypeInterface $type */
    $type = $this->typeManager->createInstance($search->getType());
    $form_state->set(['type'], $type);
    if (!$type) {
      drupal_set_message(t('No information about the type of this search was found.'), 'error');
      return [];
    }
    $form['#tree'] = TRUE;
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $search->status(),
    ];
    $form['suggester_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Suggester plugin'),
      '#description' => $this->t('Choose the suggester implementation to use.'),
      '#options' => [],
      '#required' => TRUE,
      '#default_value' => $selected_suggester_id,
      '#ajax' => [
        'callback' => 'search_api_autocomplete_suggester_ajax_callback',
        'wrapper' => 'search-api-suggester-options',
      ],
    ];

    foreach ($this->getSuggestersForIndex($search->index()) as $suggester_id => $definition) {
      // Load the suggester plugin. If the suggester is unchanged from the one
      // on the saved version of the search, use the saved configuration.
      $configuration = [];
      if ($suggester_id == $search->getSuggesterId() && $search->getOption('suggester_configuration')) {
        $configuration = $search->getOption('suggester_configuration');
      }
      $suggester = $this->suggesterManager->createInstance($suggester_id, [
        'search' => $search,
      ] + $configuration);
      if (!$suggester) {
        continue;
      }

      // Add the suggester to the suggester options.
      $form['suggester_id']['#options'][$suggester_id] = $suggester->label();

      // Then, also add the configuration form for the selected suggester.
      if ($suggester_id != $selected_suggester_id) {
        continue;
      }
      $form['options']['suggester_configuration'] = [];
      $suggester_form_state = SubFormState::createForSubform($form['options']['suggester_configuration'], $form, $form_state);
      if ($suggester_form = $suggester->buildConfigurationForm([], $suggester_form_state)) {
        $form['options']['suggester_configuration'] = $suggester_form;
        $form['options']['suggester_configuration']['#type'] = 'fieldset';
        $form['options']['suggester_configuration']['#title'] = $this->t('Configure the %suggester suggester plugin', ['%suggester' => $suggester->label()]);
        $form['options']['suggester_configuration']['#description'] = $suggester->getDescription();
        $form['options']['suggester_configuration']['#collapsible'] = TRUE;
      }
      else {
        $form['options']['suggester_configuration']['#type'] = 'item';
      }
      $form['options']['suggester_configuration']['#prefix'] = '<div id="search-api-suggester-options">';
      $form['options']['suggester_configuration']['#suffix'] = '</div>';
    }
    $form['options']['suggester_configuration']['old_suggester_id'] = [
      '#type' => 'hidden',
      '#value' => $selected_suggester_id,
      '#tree' => FALSE,
    ];

    // If there is only a single plugin available, hide the "suggester" option.
    if (count($form['suggester_id']['#options']) == 1) {
      $form['suggester_id'] = [
        '#type' => 'value',
        '#value' => key($form['suggester_id']['#options']),
      ];
    }

    $form['options']['min_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum length of keywords for autocompletion'),
      '#description' => $this->t('If the entered keywords are shorter than this, no autocomplete suggestions will be displayed.'),
      '#default_value' => $search->getOption('min_length', 1),
      '#validate' => ['element_validate_integer_positive'],
    ];
    $form['options']['results'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display result count estimates'),
      '#description' => $this->t('Display the estimated number of result for each suggestion. This option might not have an effect for some servers or types of suggestion.'),
      '#default_value' => (bool) $search->getOption('results', FALSE),
    ];
    $form['options']['delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delay in ms'),
      '#default_value' => $search->getOption('delay'),
    ];

    $custom_form = empty($form['options']['custom']) ? [] : $form['options']['custom'];
    if ($type instanceof PluginFormInterface) {
      $form['options']['custom'] = [];
      $type_form_state = SubFormState::createForSubform($form['options']['custom'], $form, $form_state);
      $form['options']['custom'] = $type->buildConfigurationForm($custom_form, $type_form_state);
    }

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['advanced']['submit_button_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search button selector'),
      '#description' => $this->t('<a href="@jquery_url">jQuery selector</a> to identify the search button in the search form. Use the ID attribute of the "Search" submit button to prevent issues when another button is present (e.g., "Reset"). The selector is evaluated relative to the form. The default value is ":submit".', ['@jquery_url' => 'https://api.jquery.com/category/selectors/']),
      '#default_value' => $search->getOption('submit_button_selector', ':submit'),
      '#required' => TRUE,
      '#parents' => ['options', 'submit_button_selector'],
    ];
    $form['advanced']['autosubmit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto-submit'),
      '#description' => $this->t('When enabled, the search form will automatically be submitted when a selection is made by pressing "Enter".'),
      '#default_value' => $search->getOption('autosubmit', TRUE),
      '#parents' => ['options', 'autosubmit'],
    ];

    return $form;
  }

  /**
   * Form AJAX handler for search_api_autocomplete_admin_search_edit().
   */
  public function search_api_autocomplete_suggester_ajax_callback(array $form, array &$form_state) {
    return $form['options']['suggester_configuration'];
  }

  /**
   * Validate callback for search_api_autocomplete_admin_search_edit().
   *
   * @see search_api_autocomplete_admin_search_edit()
   * @see search_api_autocomplete_admin_search_edit_submit()
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = &$form_state->getValues();
    // Call the config form validation method of the selected suggester plugin,
    // but only if it was the same plugin that created the form.
    if ($values['suggester_id'] == $values['old_suggester_id']) {
      $configuration = [];
      if (!empty($values['options']['suggester_configuration'])) {
        $configuration = $values['options']['suggester_configuration'];
      }
      $suggester = $this->suggesterManager->createInstance($values['suggester_id'], ['search' => $this->entity] + $configuration);
      $suggester_form = $form['options']['suggester_configuration'];
      unset($suggester_form['old_suggester_id']);
      $suggester_form_state = SubFormState::createForSubform($form['options']['suggester_configuration'], $form, $form_state);
      $suggester->validateConfigurationForm($suggester_form, $suggester_form_state);
    }

    /** @var \Drupal\search_api_autocomplete\Type\TypeInterface $type */
    $type = $form_state->get('type');
    if ($type instanceof PluginFormInterface) {
      $custom_form = empty($form['options']['custom']) ? [] : $form['options']['custom'];
      $type_form_state = SubFormState::createForSubform($form['options']['custom'], $form, $form_state);
      $type->validateConfigurationForm($custom_form, $type_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $search = $this->entity;

    $values = &$form_state->getValues();
    // Take care of custom options that aren't changed in the config form.
    if (!empty($search->getOption('custom'))) {
      if (!isset($values['options']['custom'])) {
        $values['options']['custom'] = [];
      }
      $values['options']['custom'] += $search->getOption('custom');
    }

    $search->setStatus($values['enabled']);
    $search->setSuggesterId($values['suggester_id']);
    $search->setOptions($values['options']);

    return $search;
  }

  /**
   * Submit callback for search_api_autocomplete_admin_search_edit().
   *
   * @see search_api_autocomplete_admin_search_edit()
   * @see search_api_autocomplete_admin_search_edit_validate()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = &$form_state->getValues();
    $type = $form_state->get('type');
    if ($type instanceof PluginFormInterface) {
      $custom_form = empty($form['options']['custom']) ? [] : $form['options']['custom'];
      $type_form_state = SubFormState::createForSubform($form['options']['custom'], $form, $form_state);
      $type->submitConfigurationForm($custom_form, $type_form_state);
    }

    $search = $this->entity;

    // @fixme
    // $form_state['redirect'] = 'admin/config/search/search_api/index/' . $search->index_id . '/autocomplete';
    // Allow the suggester to decide how to save its configuration. If the user
    // has disabled JS in the browser, or AJAX didn't work for some other reason,
    // a different suggester might be selected than that which created the config
    // form. In that case, we don't call the form submit method, save empty
    // configuration for the plugin and stay on the page.
    if ($values['suggester_id'] == $values['old_suggester_id']) {
      $configuration = [];
      if (!empty($values['options']['suggester_configuration'])) {
        $configuration = $values['options']['suggester_configuration'];
      }
      $suggester = $this->suggesterManager->createInstance($values['suggester_id'], [
        'search' => $search,
      ] + $configuration);
      $suggester_form = $form['options']['suggester_configuration'];
      unset($suggester_form['old_suggester_id']);
      $suggester_form_state = SubFormState::createForSubform($form['options']['suggester_configuration'], $form, $form_state);
      $suggester->submitConfigurationForm($suggester_form, $suggester_form_state);
      $values['options']['suggester_configuration'] = $suggester->getConfiguration();
    }
    else {
      $values['options']['suggester_configuration'] = [];
      $form_state['redirect'] = NULL;
      drupal_set_message(t('The used suggester plugin has changed. Please review the configuration for the new plugin.'), 'warning');
    }

    drupal_set_message(t('The autocompletion settings for the search have been saved.'));
  }

}
