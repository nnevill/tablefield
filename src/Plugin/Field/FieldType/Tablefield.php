<?php

/**
 * @file
 * Contains \Drupal\tablefield\Plugin\Field\FieldType\Tablefield.
 */

namespace Drupal\tablefield\Plugin\Field\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'tablefield' field type.
 *
 * @FieldType (
 *   id = "tablefield",
 *   label = @Translation("Table Field"),
 *   description = @Translation("Stores a table of text fields"),
 *   default_widget = "tablefield",
 *   default_formatter = "tablefield",
 *   constraints = {"PrimitiveType" = {}, "Tablefield" = {}}
 * )
 */
class Tablefield extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
        ),
        'format' => array(
          'type' => 'varchar',
          'length' => 255,
          'default value' => '',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
// @TODO tmp skip
return;
    // @todo, is this the best way to mark the default value form?
    // if we don't, it won't save the number of rows/cols
    // Allow the system settings form to have an emtpy table
    $path_args = explode('/', current_path());
    // Check if the current page is admin.
    if (\Drupal::service('router.admin_context')->isAdminRoute(\Drupal::routeMatch()->getRouteObject())) {
// @TODO this now triggers on node/edit routes in d8.
      //return FALSE;
    }
  
    // Remove the preference fields to see if the table cells are all empty
    $value = $this->get('value')->getValue();
    unset($value['tablefield']['rebuild']);
    unset($value['tablefield']['import']);
    if (!empty($value['tablefield'])) {
      foreach ($value['tablefield'] as $cell) {
        if (!empty($cell)) {
          return FALSE;
        }
      }
    }
  
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $form['export'] = array(
      '#type' => 'checkbox',
      '#title' => 'Allow users to export table data as CSV',
      '#default_value' => isset($field['settings']['export']) ? $field['settings']['export'] : FALSE,
    );
    $form['restrict_rebuild'] = array(
      '#type' => 'checkbox',
      '#title' => 'Restrict rebuilding to users with the permission "rebuild tablefield"',
      '#default_value' => isset($field['settings']['restrict_rebuild']) ? $field['settings']['restrict_rebuild'] : FALSE,
    );
    $form['lock_values'] = array(
      '#type' => 'checkbox',
      '#title' => 'Lock table field defaults from further edits during node add/edit.',
      '#default_value' => isset($field['settings']['lock_values']) ? $field['settings']['lock_values'] : FALSE,
    );
    $form['cell_processing'] = array(
      '#type' => 'radios',
      '#title' => t('Table cell processing'),
      '#default_value' => isset($field['settings']['cell_processing']) ? $field['settings']['cell_processing'] : 0,
      '#options' => array(
        t('Plain text'),
        t('Filtered text (user selects input format)')
      ),
    );
    $form['default_message'] = array(
      '#type' => 'markup',
      '#value' => t('To specify a default table, use the &quot;Default Value&quot; above. There you can specify a default number of rows/columns and values.'),
    );
  
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $value = $this->get('value')->getValue();

    if (empty($value['value'])) {
      $tablefield = array();
      if (!empty($value['tablefield'])) {
        foreach ($value['tablefield'] as $key => $value) {
          $tablefield[$key] = $value;
        }
      }
// @TODO is this the correct way to save the data in D8?
      $this->set('value', serialize($tablefield));
    }
    //elseif (empty($value['tablefield'])) {
// @TODO deal with this :/
      // Batch processing only provides the 'value'
      //$value['tablefield'] = unserialize($value['value']);
    //}
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(
      FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Table data'))
      ->setDescription(t('Stores tabular data.'));
    return $properties;
  }

}
