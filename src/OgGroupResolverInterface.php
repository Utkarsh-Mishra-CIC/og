<?php

namespace Drupal\og;

/**
 * Defines an interface for OgGroupResolver plugins.
 *
 * These plugins are used to discover which groups are relevant in the current
 * context. Each plugin is responsible for finding groups in a particular
 * domain. For example, we can have a plugin that checks if we are on the
 * canonical URL of a group entity, and can take the group entity from the
 * route object.
 *
 * Sometimes a plugin might return multiple relevant groups, for example if it
 * finds a group content entity on the route that belongs to multiple groups.
 *
 * These plugins are invoked by OgContext::getRuntimeContexts() which then
 * interprets the results and makes an educated guess at the group which is most
 * relevant in the current context.
 *
 * @see \Drupal\og\ContextProvider\OgContext::getRuntimeContexts()
 */
interface OgGroupResolverInterface {

  /**
   * Returns the groups that were resolved by the plugin.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of groups.
   */
  public function getGroups();

  /**
   * Declares that no further group resolving is necessary.
   *
   * Use this if the plugin has determined the relevant group in the current
   * context with 100% certainty.
   */
  public function stopPropagation();

  /**
   * Returns whether the group resolving process can be stopped.
   *
   * @return bool
   *   TRUE if no further group resolving is necessary. FALSE otherwise.
   */
  public function isPropagationStopped();

  /**
   * Returns a list of cache contexts that will affect the group resolving.
   *
   * This allows a plugin to share which cache contexts are relevant for the
   * group(s) it has resolved. For example, a plugin may have found a group by
   * inspecting the current route ('route'), user session ('user'), by checking
   * the domain name ('site'), or a combination of those.
   *
   * @return string[]
   *   An array of cache context IDs.
   */
  public function getCacheContextIds();

}
