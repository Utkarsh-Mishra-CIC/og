<?php

namespace Drupal\og;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for OgDeleteOrphans plugins.
 *
 * Depending on the needs of a project there are different ways to deal with
 * orphaned memberships and content after a group entity is deleted. This
 * plugin type allows to register a group entity for having its orphans deleted,
 * to customize the query that will gather the orphans, and to start the
 * deletion process.
 *
 * It is up to the implementing plugin to deal with the specifics. A long
 * running batch process will need to store the list of orphans somewhere, and
 * will be responsible for running the deletion to the end.
 */
interface OgDeleteOrphansInterface {

  /**
   * Registers a group entity for deletion of its orphaned members and content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity to register.
   */
  public function register(\Drupal\Core\Entity\EntityInterface $entity);

  /**
   * Queries the registered group entity for orphaned members to delete.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of member entities to delete.
   */
  public function query();

  /**
   * Starts the deletion process.
   */
  public function process();

  /**
   * Returns the configuration form elements specific to this plugin.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   */
  public function configurationForm($form, FormStateInterface $form_state);

  /**
   * Validation handler for the plugin configuration form.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\og\OgDeleteOrphansInterface::configurationForm()
   * @see \Drupal\og\OgDeleteOrphansInterface::configurationFormSubmit()
   */
  public function configurationFormValidate($form, FormStateInterface $form_state);

  /**
   * Submit handler for the plugin configuration form.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\og\OgDeleteOrphansInterface::configurationForm()
   * @see \Drupal\og\OgDeleteOrphansInterface::configurationFormValidate()
   */
  public function configurationFormSubmit($form, FormStateInterface $form_state);

}
