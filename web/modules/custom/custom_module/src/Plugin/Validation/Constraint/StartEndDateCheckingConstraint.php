<?php

namespace Drupal\custom_module\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if End Date not empty.
 * Start Date must be not empty too
 * End Date must be greater then Start Date.
 *
 * @Constraint(
 *   id = "StartEndDateChecking",
 *   label = @Translation("Start/End Date checking", context = "Validation")
 * )
 */
class StartEndDateCheckingConstraint extends Constraint {

  public $message = 'End date cannot be before the start date.';

}
