<?php

/**
 * @file
 * Contains \Drupal\tablefield\Plugin\Validation\Constraint;
 */
namespace Drupal\tablefield\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Validation constraint for tablefield data.
 *
 * @Plugin(
 *   id = "Tablefield",
 *   label = @Translation("Tablefield data.", context = "Validation"),
 * )
 */
class TablefieldConstraint extends Constraint implements ConstraintValidatorInterface {

  public $message = 'Tablefield data is not valid.';

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Catch empty form submissions for required tablefields
// @TODO not sure how to implement this here.
/*
    if ($instance['required'] && isset($value) && tablefield_field_is_empty($items[0], $field)) {
      $message = t('@field is a required field.', array('@field' => $instance['label']));
      $errors[$field['field_name']][$langcode][0]['tablefield'][] = array(
        'error' => 'empty_required_tablefield',
        'message' => $message,
      );
    }
*/
  }
}


