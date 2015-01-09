<?php

/**
 * @file
 * Contains \Drupal\tablefield\Plugin\Field\FieldType\Tablefield.
 */

namespace Drupal\tablefield\Plugin\Field\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
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
class TablefieldItem extends FieldItemBase {

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
    $value = $this->getValue();

    // Remove the extra keys to see if the table cells are all empty
    unset($value['tablefield']['rebuild']);
    unset($value['tablefield']['import']);

    // if tablefield is same as the field settings, then consider it empty (but only for node edit forms)
    /*if (empty($value['is_field_settings'])) {
      $field = $this->getFieldDefinition();
      if (!empty($field->default_value[$this->name])) {
        $default_value = $field->default_value[$this->name];
      }
      else {
        $default_value = $field->default_value[0];
      }

      $default_value['tablefield'] = unserialize($default_value['value']);
      unset($default_value['tablefield']['rebuild']);
      unset($default_value['tablefield']['import']);

      if ($value['tablefield'] == $default_value['tablefield']) {
        return TRUE;
      }
    }*/

    // Allow the field settings form to have at least one emtpy table
    if (!empty($value['is_field_settings']) && $this->name == 0) {
      return FALSE;
    }


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
  public static function defaultFieldSettings() {
    return array(
      'export' => 0,
      'restrict_rebuild' => 1,
      'lock_values' => 0,
      'cell_processing' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $settings = $this->getSettings();

    $form['export'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to export table data as CSV'),
      '#default_value' => $settings['export'],
    );
    $form['restrict_rebuild'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict rebuilding to users with the permission "rebuild tablefield"'),
      '#default_value' => $settings['restrict_rebuild'],
    );
    $form['lock_values'] = array(
      '#type' => 'checkbox',
      '#title' => 'Lock table field defaults from further edits during node add/edit.',
      '#default_value' => $settings['lock_values'],
    );
    $form['cell_processing'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Table cell processing'),
      '#default_value' => $settings['cell_processing'],
      '#options' => array(
        $this->t('Plain text'),
        $this->t('Filtered text (user selects input format)')
      ),
    );
    $form['default_message'] = array(
      '#type' => 'markup',
      '#value' => $this->t('To specify a default table, use the &quot;Default Value&quot; above. There you can specify a default number of rows/columns and values.'),
    );
  
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Table data'))
      ->setDescription(t('Stores tabular data.'));

    $properties['format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    // @TODO: is this needed? Does it actually work?
    // https://www.drupal.org/node/2112677
    /*$properties['tablefield'] = DataDefinition::create('map')
      ->setLabel(t('Rationalized tablefield'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\tablefield\TablefieldProcessed')
      ->setSetting('tablefield source', 'value');*/

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (!empty($values['value']) && empty($values['tablefield'])) {
      $values['tablefield'] = unserialize($values['value']);
    }
    parent::setValue($values, $notify);
  }


  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @TODO should field definition be counted?
    $tablefield = array(
      'cell_0_0' => 'Sample 1',
      'cell_0_1' => 'Sample 2',
      'rebuild' => ['count_rows' => 1, 'count_cols' => 2],
    );
    $values['value'] = serialize($tablefield);
    return $values;
  }


}
