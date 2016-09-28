<?php

namespace Drupal\og\Plugin\OgGroupResolver;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgGroupResolverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the group from the query arguments on the request.
 *
 * This plugin inspects the current request and checks if there are query
 * arguments available that point to a group entity.
 *
 * @OgGroupResolver(
 *   id = "request_query_argument",
 *   label = "Group entity from query arguments",
 *   description = @Translation("Checks if the current request has query arguments that indicate the group context.")
 * )
 */
class RequestQueryArgumentResolver extends OgGroupResolverBase implements ContainerFactoryPluginInterface {

  /**
   * The query argument that holds the group entity type.
   */
  const GROUP_TYPE_ARGUMENT = 'og-type';

  /**
   * The query argument that holds the group entity ID.
   */
  const GROUP_ID_ARGUMENT = 'og-id';

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The resolved group.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null|false
   *   The resolved group, or NULL if no group has been resolved, or FALSE if
   *   resolution has not yet taken place.
   */
  protected $group = FALSE;

  /**
   * Constructs a RequestQueryArgumentResolver.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, GroupTypeManager $group_type_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->groupTypeManager = $group_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('og.group_type_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    if ($this->group === FALSE) {
      $this->resolve();
    }
    return $this->group ?: [];
  }

  /**
   * Resolve the group from the query arguments.
   *
   * The resolved group will be cached locally in $this->group.
   */
  protected function resolve() {
    $this->group = NULL;

    // Check if our arguments are present on the request.
    $query = $this->requestStack->getCurrentRequest()->query;
    if ($query->has(self::GROUP_TYPE_ARGUMENT) && $query->has(self::GROUP_ID_ARGUMENT)) {
      try {
        $storage = $this->entityTypeManager->getStorage($query->get(self::GROUP_TYPE_ARGUMENT));
      }
      catch (InvalidPluginDefinitionException $e) {
        // Invalid entity type specified, cannot resolve group.
        return;
      }

      // Load the entity and check if it is a group before setting it.
      if ($entity = $storage->load($query->get(self::GROUP_ID_ARGUMENT))) {
        if ($this->groupTypeManager->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
          $this->group = $entity;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContextIds() {
    return ['url'];
  }

}
