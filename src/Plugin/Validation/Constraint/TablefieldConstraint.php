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
    if ($value->getFieldDefinition()->isRequired() && $value->isEmpty()) {
      $this->buildViolation($constraint->message)
        ->addViolation();
    }
  }
}


