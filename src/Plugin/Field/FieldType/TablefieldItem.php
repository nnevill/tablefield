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
 *   default_formatter = "tablefield"
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
  public static function defaultFieldSettings() {
    return array(
      'export' => 0,
      'restrict_rebuild' => 1,
      'restrict_import' => 1,
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
    $form['restrict_import'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict importing to users with the permission "import tablefield"'),
      '#default_value' => $settings['restrict_import'],
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

    return $properties;
  }


  public function setValue($value, $notify = TRUE) {
    // if text_format is enabled, the tablefield is shifted under value
    if (!empty($value['value']['tablefield']['table'])) {
      //$value['tablefield'] = $value['value']['tablefield'];
      // locked values can get at the end of the table, need to sort
      ksort($value['value']['tablefield']['table']);
      $value['value'] = serialize($value['value']['tablefield']['table']);
    }
    // same as above, but with text_format disabled
    else if (empty($value['value']) && !empty($value['tablefield']['table'])) {
      ksort($value['tablefield']['table']);
      $value['value'] = serialize($value['tablefield']['table']);
      unset($value['tablefield']);
    }
    // value just read from storage; needs to be unserialized before setting the Tablefield item
    else if (!empty($value['value']) && !is_array($value['value'])) {
      $value['value'] = unserialize($value['value']);
      $value['rebuild']['rows'] = isset($value['value']) ? count($value['value']) : 0;
      $value['rebuild']['cols'] = isset($value['value'][0]) ? count($value['value'][0]) : 0;
    }

    parent::setValue($value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = parent::getValue();
    if (!empty($value['value']) && !is_array($value['value'])) {
      $value['value'] = unserialize($value['value']);
      $value['rebuild']['rows'] = isset($value['value']) ? count($value['value']) : 0;
      $value['rebuild']['cols'] = isset($value['value'][0]) ? count($value['value'][0]) : 0;
    }
    return $value;
  }


  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @TODO should field definition be counted?
    return array(
      'value' => [['Header 1', 'Header 2'], ['Data 1', 'Data 2']],
      'rebuild' => ['rows' => 2, 'cols' => 2],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getValue();

    // Allow the field settings form to have at least one emtpy table
    if (!empty($value['is_field_settings']) && $this->name == 0) {
      return FALSE;
    }

    if (is_array($value['value'])) {
      foreach ($value['value'] as $row) {
        foreach ($row as $cell) {
          if (!empty($cell)) {
            return FALSE;
          }
        }
      }
    }

    return TRUE;
  }

}
