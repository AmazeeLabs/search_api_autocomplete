<?php

namespace Drupal\search_api_autocomplete\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\Entity\Search;
use Drupal\search_api_autocomplete\Type\TypeManager;
use Drupal\search_api_autocomplete\Utility\PluginHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the overview of all search autocompletion configurations.
 */
class IndexOverviewForm extends FormBase {

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
   * The plugin helper.
   *
   * @var \Drupal\search_api_autocomplete\Utility\PluginHelperInterface
   */
  protected $pluginHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Creates a new AutocompleteSearchAdminOverview instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $suggester_manager
   *   The suggester manager.
   * @param \Drupal\search_api_autocomplete\Type\TypeManager $autocomplete_type_manager
   *   The autocomplete type manager.
   * @param \Drupal\search_api_autocomplete\Utility\PluginHelperInterface $plugin_helper
   *   The plugin helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   */
  public function __construct(PluginManagerInterface $suggester_manager, TypeManager $autocomplete_type_manager, PluginHelperInterface $plugin_helper, EntityTypeManagerInterface $entity_type_manager, RedirectDestinationInterface $redirect_destination) {
    $this->suggesterManager = $suggester_manager;
    $this->typeManager = $autocomplete_type_manager;
    $this->pluginHelper = $plugin_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search_api_autocomplete.suggester'),
      $container->get('plugin.manager.search_api_autocomplete.type'),
      $container->get('search_api_autocomplete.plugin_helper'),
      $container->get('entity_type.manager'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_autocomplete_admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, IndexInterface $search_api_index = NULL) {
    $form_state->set('index', $search_api_index);
    $index_id = $search_api_index->id();

    $form['#tree'] = TRUE;

    $searches = $this->loadAutocompleteSearchByIndex($index_id);
    $searches_by_type = [];
    $unavailable_searches = [];
    $no_suggesters = [];
    $any_suggester = FALSE;

    $suggesters = array_map(function ($suggester_info) {
      return $suggester_info['class'];
    }, $this->suggesterManager->getDefinitions());

    /** @var \Drupal\search_api_autocomplete\Type\TypeInterface[] $types */
    $types = array_map(function ($definition) {
      return $this->typeManager->createInstance($definition['id']);
    }, $this->typeManager->getDefinitions());
    foreach ($types as $type => $autocomplete_type) {
      $t_searches = $autocomplete_type->listSearches($search_api_index);
      foreach ($t_searches as $id => $search) {
        if (isset($searches[$id])) {
          $searches_by_type[$type][$id] = $searches[$id];
          unset($searches[$id]);
        }
        else {
          $search += [
            'id' => $id,
            'status' => FALSE,
            'label' => $id,
            'index_id' => $index_id,
            'type_settings' => [
              $type => [],
            ],
            'options' => [],
          ];
          $search = Search::create($search);
          $available_suggesters = array_filter($suggesters, function ($suggester_class) use ($search) {
            return $suggester_class::supportsSearch($search);
          });
          if ($available_suggesters) {
            reset($available_suggesters);
            $suggester_id = key($available_suggesters);
            $suggester = $this->pluginHelper->createSuggesterPlugin($search, $suggester_id);
            $search->setSuggesters([
              $suggester_id => $suggester,
            ]);
            $searches_by_type[$type][$id] = $search;
          }
          else {
            $no_suggesters[$id] = $search->label();
          }
        }
      }
    }

    // Add settings for unknown searches to list, so they can be deleted.
    foreach ($searches as $id => $search) {
      $type = isset($types[$search->getTypeId()]) ? $search->getTypeId() : '';
      $searches_by_type[$type][$id] = $search;
      $unavailable_searches[$type][$id] = TRUE;
    }

    /** @var \Drupal\search_api_autocomplete\Type\TypeInterface $autocomplete_type */
    foreach ($types as $type => $autocomplete_type) {
      if (empty($searches_by_type[$type])) {
        continue;
      }
      if (!$type) {
        $info = [
          'name' => $this->t('Unavailable search types'),
          'description' => $this->t("The modules providing these searches were disabled or uninstalled. If you won't use them anymore, you can delete their settings."),
        ];
      }
      elseif (!empty($info['unavailable'])) {
        $info['description'] .= '</p><p>' . $this->t("The searches marked with an asterisk (*) are currently not available, possibly because they were deleted. If you won't use them anymore, you can delete their autocomplete settings.");
      }
      $form[$type] = [
        '#type' => 'fieldset',
        '#title' => $autocomplete_type->label(),
      ];
      if ($description = $autocomplete_type->getDescription()) {
        $form[$type]['#description'] = '<p>' . $description . '</p>';
      }
      $form[$type]['searches']['#type'] = 'tableselect';
      $form[$type]['searches']['#header'] = [
        'label' => $this->t('label'),
        'operations' => $this->t('Operations'),
      ];
      $form[$type]['searches']['#empty'] = '';
      $form[$type]['searches']['#js_select'] = TRUE;
      /** @var \Drupal\search_api_autocomplete\SearchInterface $search */
      foreach ($searches_by_type[$type] as $id => $search) {
        $any_suggester |= $search->getSuggesterIds();

        $form[$type]['searches'][$id] = [
          '#type' => 'checkbox',
          '#default_value' => $search->status(),
          '#parents' => ['searches', $id],
        ];
        $unavailable = !empty($info['unavailable'][$id]);
        if ($unavailable) {
          $form[$type]['searches'][$id]['#default_value'] = FALSE;
          $form[$type]['searches'][$id]['#disabled'] = TRUE;
        }
        $form_state->set(['searches', $id], $search);
        $options = &$form[$type]['searches']['#options'][$id];
        $options['label'] = $search->label();
        if ($unavailable) {
          $options['label'] = '* ' . $options['label'];
        }
        $items = [];
        if (!$unavailable && !empty($search->status())) {
          $items[] = [
            'title' => $this->t('Edit'),
            'url' => $search->toUrl('edit-form'),
          ];
          $items[] = [
            'title' => $this->t('Delete'),
            'url' => $search->toUrl('delete-form'),
          ];
        }

        if ($items) {
          $options['operations'] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $items,
            ],
          ];
        }
        else {
          $options['operations'] = '';
        }
        unset($options);
      }
    }

    if ($no_suggesters) {
      if (!$any_suggester) {
        $args = [
          '@feature' => 'search_api_autocomplete',
          ':backends_url' => 'https://www.drupal.org/docs/8/modules/search-api/getting-started/server-backends-and-features#backends',
        ];
        drupal_set_message($this->t('There are currently no suggester plugins installed that support this index. To solve this problem, you can either:<ul><li>move the index to a server which supports the "@feature" feature (see the <a href=":backends_url">available backends</a>);</li><li>or install a module providing a new suggester plugin that supports this index.</li></ul>', $args), 'error');
        if ($this->loadAutocompleteSearchByIndex($index_id)) {
          $form['description'] = [
            '#type' => 'item',
            '#title' => $this->t('Delete autocompletion settings'),
            '#description' => $this->t("If you won't use autocompletion with this index anymore, you can delete all autocompletion settings associated with it. This will delete all autocompletion settings on this index. Settings on other indexes won't be influenced."),
          ];
          $form['button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Delete autocompletion settings'),
            '#submit' => [$this, 'submitDelete'],
          ];
        }
        return $form;
      }
      else {
        $args = [
          '@searches' => implode(', ', $no_suggesters),
        ];
        drupal_set_message($this->t('The following searches cannot be enabled because no suggesters are available for them: @searches.', $args));
      }
    }

    if (!Element::children($form)) {
      $form['message']['#markup'] = '<p>' . $this->t('There are currently no searches known for this index.') . '</p>';
    }
    else {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messages = $this->t('The settings have been saved.');
    foreach ($form_state->getValue('searches') as $id => $enabled) {
      /** @var \Drupal\search_api_autocomplete\SearchInterface $search */
      $search = $form_state->get(['searches', $id]);
      if ($search->status() != $enabled) {
        $change = TRUE;
        if (!empty($search)) {
          $options['query'] = $this->redirectDestination->getAsArray();
          $options['fragment'] = 'module-search_api_autocomplete';
          $vars[':perm_url'] = Url::fromRoute('user.admin_permissions', [], $options)->toString();
          $messages = $this->t('The settings have been saved. Please remember to set the <a href=":perm_url">permissions</a> for the newly enabled searches.', $vars);
        }
        $search->setStatus($enabled);
        $search->save();
      }
    }
    drupal_set_message(empty($change) ? $this->t('No values were changed.') : $messages);
  }

  /**
   * Form submission handler for deleting an autocomplete search.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitDelete(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $form_state->get('index');
    $ids = array_keys($this->loadAutocompleteSearchByIndex($index->id()));
    if ($ids) {
      $autocomplete_search_storage = $this->entityTypeManager->getStorage('search_api_autocomplete_search');
      $autocomplete_searches = $autocomplete_search_storage->loadMultiple($ids);
      $autocomplete_search_storage->delete($autocomplete_searches);
      drupal_set_message($this->t('All autocompletion settings stored for this index were deleted.'));
    }
    else {
      drupal_set_message($this->t('There were no settings to delete.'), 'warning');
    }
    $form_state->setRedirectUrl($index->toUrl());
  }

  /**
   * Load the autocomplete plugins for an index.
   *
   * @param string $index_id
   *   The index ID.
   *
   * @return \Drupal\search_api_autocomplete\SearchInterface[]
   *   An array of autocomplete plugins.
   */
  protected function loadAutocompleteSearchByIndex($index_id) {
    return $this->entityTypeManager
      ->getStorage('search_api_autocomplete_search')
      ->loadByProperties([
        'index_id' => $index_id,
      ]);
  }

}
