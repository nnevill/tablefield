<?php

/**
 * @file
 * Contains \Drupal\tablefield\Form\TablefieldConfigForm.
 */
namespace Drupal\tablefield\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;

class TablefieldConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'tablefield_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['tablefield_csv_separator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('CSV separator'),
      '#size' => 1,
      '#maxlength' => 1,
      '#default_value' => \Drupal::config('tablefield.settings')->get('tablefield_csv_separator'),
      '#description' => $this->t('Select the separator for the CSV import/export.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (Unicode::strlen($form_state->getValue('tablefield_csv_separator')) !== 1) {
      $message = $this->t('Separator must be one character only!');
      $this->setFormError('tablefield_csv_separator', $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tablefield.settings')
      ->set('tablefield_csv_separator', $form_state->getValue('tablefield_csv_separator'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
