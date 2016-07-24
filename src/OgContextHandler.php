<?php

namespace Drupal\og;

/**
 * @file
 * Contains \Drupal\og\OgContextHandler.
 */

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class OgContextHandler.
 *
 * @package Drupal\og
 */
class OgContextHandler implements OgContextHandlerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OG context manager.
   *
   * @var \Drupal\og\OgContextManager
   */
  protected $pluginManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs an OgManager service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\og\OgContextManager $context_manager
   *   The OG context manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OgContextManager $context_manager, EntityTypeManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $context_manager;
    $this->storage = $entity_manager->getStorage('og_context_negotiation');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    $plugins = $this->getPlugins();

    foreach ($plugins as $plugin) {
      if ($group = $this->getPlugin($plugin['id'])->getGroup()) {
        return $group;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($return_mode = OgContextHandlerInterface::RETURN_ONLY_ACTIVE) {

    /** @var OgContextNegotiation[] $og_context_config */
    $og_context_config = $this->storage->loadMultiple();

    $plugins = $this->pluginManager->getDefinitions();

    if ($return_mode != OgContextHandlerInterface::RETURN_ALL) {

      foreach ($og_context_config as $context) {
        if ($return_mode == OgContextHandlerInterface::RETURN_ONLY_ACTIVE) {
          $condition = $context->get('status') == FALSE;
        }
        else {
          $condition = !in_array($context->id(), array_keys($plugins));
        }

        if ($condition) {
          unset($plugins[$context->id()]);
        }
      }
    }

    if (!empty($og_context_config)) {
      uasort($plugins, function ($a, $b) use ($og_context_config) {
        return $og_context_config[$a['id']]->get('weight') > $og_context_config[$b['id']]->get('weight') ? 1 : -1;
      });
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin($plugin_id) {
    return $this->pluginManager->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function updatePlugin($plugin_id, $config = []) {
    /** @var OgContextNegotiation $contex */
    $context = $this->storage->load($plugin_id);

    foreach ($config as $key => $value) {
      $context->set($key, $value);
    }

    $context->save();
  }

  /**
   * {@inheritdoc}
   */
  public function updateConfigStorage() {
    $plugins = $this->getPlugins(['return_mode' => OgContextHandlerInterface::RETURN_ALL]);

    $og_context_storage = $this->storage;
    $og_context_config = $og_context_storage->loadMultiple();

    $weight = 0;
    foreach ($plugins as $plugin) {
      if (in_array($plugin['id'], array_keys($og_context_config))) {
        // The negotiation plugin already registered.
        continue;
      }

      // Registering a new negotiation plugin.
      $og_context_storage->create([
        'id' => $plugin['id'],
        'label' => $plugin['label'],
        'description' => $plugin['description'],
        'status' => FALSE,
        'weight' => $weight,
      ])->save();

      $weight++;
    }
  }

}
