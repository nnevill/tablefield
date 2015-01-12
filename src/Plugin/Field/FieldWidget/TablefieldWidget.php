<?php

/**
 * @file
 * Contains \Drupal\tablefield\Plugin\Field\FieldWidget\TablefieldWidget.
 */

namespace Drupal\tablefield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;


/**
 * Plugin implementation of the 'tablefield' widget.
 *
 * @FieldWidget (
 *   id = "tablefield",
 *   label = @Translation("Table Field"),
 *   field_types = {
 *     "tablefield"
 *   },
 * )
 */
class TablefieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $is_field_settings_default_widget_form = $form_state->getBuildInfo()['form_id'] == 'field_ui_field_edit_form' ? 1 : 0;

    $field = $items[0]->getFieldDefinition();
    $field_name = $field->getName();
    $field_settings = $field->getSettings();

    $triggering_element = $form_state->getTriggeringElement();
  
    $element['#type'] = 'tablefield';
    $element['#attached']['library'][] = 'tablefield/form_css';
    $form['#attributes']['enctype'] = 'multipart/form-data';
  
    // Establish a list of saved/submitted/default values
    // Start with Rebuilding table rows/cols
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield_rebuild_' . $field_name . '_' . $delta) {
      if ($is_field_settings_default_widget_form) {
        // This means we are currently in the field settings form
        $default_value = $form_state->getValue(['default_value_input', $field_name, $delta]);
      }
      else {
        // node edit form
        $default_value = $form_state->getValue([$field_name, $delta]);
      }
      drupal_set_message($this->t('Table structure rebuilt.'), 'status', FALSE);
    }
    elseif (isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield_import_' . $field_name . '-' . $delta) {
      // Import CSV
      $tablefield = $this->importCsv($field_name .'_'. $delta);

      $default_value['tablefield'] = $tablefield;

      $form_state->setValue([$field_name, $delta, 'tablefield'], $tablefield);

      // Unfortunately no nice cherry-pick setUserInput available, have to do it long way
      $input = $form_state->getUserInput();
      $input[$field_name][$delta]['tablefield'] = $tablefield;
      $form_state->setUserInput($input);
    }
    // @TODO: does this ever evaluate to TRUE?
    elseif ($form_state->isSubmitted() && isset($items[$delta]) && isset($items[$delta]->tablefield)) {
      // A form was submitted
      $default_value = $items[$delta];
    }
    elseif (isset($items[$delta]->value)) {
      $default_value['tablefield'] = unserialize($items[$delta]->value);
    }
    elseif (!$is_field_settings_default_widget_form && !empty($field->default_value[$delta])) {
      $default_value = $field->default_value[$delta];
      $default_value['tablefield'] = unserialize($default_value['value']);
    }

    $element['tablefield'] = array(
      '#title' => $element['#title'],
      '#description' => $this->t('The first row will appear as the table header. Leave the first row blank if you do not need a header.'),
      '#attributes' => array('id' => 'form-tablefield-' . $field_name . '-' . $delta, 'class' => array('form-tablefield')),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#prefix' => '<div id="tablefield-' . $field_name . '-' . $delta . '-wrapper">',
      '#suffix' => '</div>',
    );

    // Give the fieldset the appropriate class if it is required
    if ($element['#required']) {
      $element['tablefield']['#required'] = TRUE;
    }
  
    // Render the form table
    $element['tablefield']['a_break'] = array(
      '#markup' => '<table>',
    );

    $count_cols = isset($default_value['tablefield']['rebuild']['count_cols']) ? $default_value['tablefield']['rebuild']['count_cols'] : 5;
    $count_rows = isset($default_value['tablefield']['rebuild']['count_rows']) ? $default_value['tablefield']['rebuild']['count_rows'] : 5;

    for ($i = 0; $i < $count_rows; $i++) {
      $zebra = $i % 2 == 0 ? 'even' : 'odd';
      $element['tablefield']['b_break' . $i] = array(
        '#markup' => '<tr class="tablefield-row-' . $i . ' ' . $zebra . '">',
      );
      for ($ii = 0; $ii < $count_cols; $ii++) {
        $cell_default = isset($default_value['tablefield']["cell_${i}_${ii}"]) ? $default_value['tablefield']["cell_${i}_${ii}"] : '';
        if (!empty($cell_default) && !empty($field_settings['lock_values']) && !$is_field_settings_default_widget_form) {
          // The value still needs to be send on every load in order for the
          // table to be saved correctly.
          $element['tablefield']['cell_' . $i . '_' . $ii] = array(
            '#type' => 'value',
            '#value' => $cell_default,
          );
          // Display the default value, since it's not editable.
          $element['tablefield']['cell_' . $i . '_' . $ii . '_display'] = array(
            '#type' => 'item',
            '#title' => $cell_default,
            '#prefix' => '<td style="width:' . floor(100/$count_cols) . '%">',
            '#suffix' => '</td>',
          );
        }
        else {
          $element['tablefield']['cell_' . $i . '_' . $ii] = array(
            '#type' => 'textfield',
            '#maxlength' => 2048,
            '#size' => 0,
            '#attributes' => array(
              'id' => 'tablefield_' . $delta . '_cell_' . $i . '_' . $ii,
              'class' => array('tablefield-row-' . $i, 'tablefield-col-' . $ii),
              'style' => 'width:100%'
            ),
            '#default_value' => (empty($field_value)) ? $cell_default : $field_value,
            '#prefix' => '<td style="width:' . floor(100/$count_cols) . '%">',
            '#suffix' => '</td>',
          );
        }
      }
      $element['tablefield']['c_break' . $i] = array(
        '#markup' => '</tr>',
      );
    }

    $element['tablefield']['t_break' . $i] = array(
      '#markup' => '</table>',
    );
  
    // If the user doesn't have rebuild perms, we pass along the data as a value.
    // Otherwise, we will provide form elements to specify the size and ajax rebuild.
    if (!empty($field_settings['restrict_rebuild']) && !\Drupal::currentUser()->hasPermission('rebuild tablefield')) {
      $element['tablefield']['rebuild'] = array (
        '#type' => 'value',
        '#tree' => TRUE,
        'count_cols' => array(
          '#type' => 'value',
          '#value' => $count_cols,
        ),
        'count_rows' => array(
          '#type' => 'value',
          '#value' => $count_rows,
        ),
        'rebuild' => array(
          '#type' => 'value',
          '#value' => $this->t('Rebuild Table'),
        ),
      );
    }
    else {
      $element['tablefield']['rebuild'] = array(
        '#type' => 'details',
        '#tree' => TRUE,
        '#title' => $this->t('Change number of rows/columns.'),
        '#open' => FALSE,
      );
      $element['tablefield']['rebuild']['count_cols'] = array(
        '#title' => $this->t('How many Columns'),
        '#type' => 'textfield',
        '#size' => 5,
        '#prefix' => '<div class="clearfix">',
        '#suffix' => '</div>',
        '#default_value' => $count_cols,
      );
      $element['tablefield']['rebuild']['count_rows'] = array(
        '#title' => $this->t('How many Rows'),
        '#type' => 'textfield',
        '#size' => 5,
        '#prefix' => '<div class="clearfix">',
        '#suffix' => '</div>',
        '#default_value' => $count_rows,
      );
      $element['tablefield']['rebuild']['rebuild'] = array(
        '#type' => 'button',
        '#value' => $this->t('Rebuild Table'),
        '#name' => 'tablefield_rebuild_' . $field_name . '_' . $delta,
        '#attributes' => array(
          'class' => array('tablefield-rebuild'),
        ),
        '#ajax' => array(
          'callback' => 'Drupal\tablefield\Plugin\Field\FieldWidget\TablefieldWidget::ajaxCallbackRebuild',
          'progress' => array('type' => 'throbber', 'message' => NULL),
          'wrapper' => 'tablefield-' . $field_name . '-' . $delta . '-wrapper',
          'effect' => 'fade',
        ),
      );
    }

    // Allow the user to import a csv file
    $element['tablefield']['import'] = array(
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Import from CSV'),
      '#open' => FALSE,
    );
    $element['tablefield']['import']['tablefield_csv_' . $field_name . '_' . $delta] = array(
      '#name' => 'files[' . $field_name . '_' . $delta . ']',
      '#title' => 'File upload',
      '#type' => 'file',
    );
  
    $element['tablefield']['import']['rebuild_' . $field_name . '_' . $delta] = array(
      '#type' => 'button',
      '#validate' => array(),
      '#value' => $this->t('Upload CSV'),
      '#name' => 'tablefield_import_' . $field_name . '-' . $delta,
      '#attributes' => array(
        'class' => array('tablefield-rebuild'),
        //'id' => 'tablefield-import-button-' . $field['field_name'] . '-' . $delta,
      ),
      '#ajax' => array(
        'callback' => 'Drupal\tablefield\Plugin\Field\FieldWidget\TablefieldWidget::ajaxCallbackRebuild',
        'progress' => array('type' => 'throbber', 'message' => NULL),
        'wrapper' => 'tablefield-' . $field_name . '-' . $delta . '-wrapper',
        'effect' => 'fade',
      ),
    );


    if ($is_field_settings_default_widget_form) {
      $element['tablefield']['#description'] = t('This form defines the table field defaults, but the number of rows/columns and content can be overridden on a per-node basis. The first row will appear as the table header. Leave the first row bland if you do not need a header.');

      // This we need in the TablefieldItem::isEmpty check
      $element['is_field_settings'] = array(
        '#type' => 'value',
        '#value' => 1,
      );
    }
  
    // Allow the user to select input filters
    if (!empty($field_settings['cell_processing'])) {
      $element['#base_type'] = $element['#type'];
      $element['#type'] = 'text_format';
      $element['#format'] = isset($items[$delta]->format) ? $items[$delta]->format : NULL;
    }

    $element['#element_validate'][] = array($this, 'validateTablefield');
  
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      $values[$delta]['value'] = serialize($value['tablefield']);
    }
    return $values;
  }

  /**
   * AJAX callback to rebuild the number of rows/columns.
   * The basic idea is to descend down the list of #parent elements of the
   * clicked_button in order to locate the tablefield inside of the $form
   * array. That is the element that we need to return.
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function ajaxCallbackRebuild(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    if ($form['#form_id'] == 'field_ui_field_edit_form') {
      $rebuild = $form['field']['default_value']['widget'][$triggering_element['#parents'][2]];
    }
    else {
      $rebuild = $form[$triggering_element['#parents'][0]]['widget'][$triggering_element['#parents'][1]];
    }

    // We don't want to re-send the format/_weight options.
    unset($rebuild['format']);
    unset($rebuild['_weight']);

    return $rebuild;
  }

  /**
   * Helper function to import data from a CSV file
   * @param string $form_field_name
   * @return array $tablefield
   */
  function importCsv($form_field_name) {
    $file_upload = \Drupal::request()->files->get("files[$form_field_name]", NULL, TRUE);
    if (!empty($file_upload) && $handle = fopen($file_upload->getPathname(), 'r'))  {
      // Populate CSV values
      $tablefield = array();
      $max_col_count = 0;
      $row_count = 0;

      $separator = \Drupal::config('tablefield.settings')->get('tablefield_csv_separator');
      while (($csv = fgetcsv($handle, 0, $separator)) !== FALSE) {
        $col_count = count($csv);
        foreach ($csv as $col_id => $col) {
          $tablefield['cell_' . $row_count . '_' . $col_id] = $col;
        }
        $max_col_count = $col_count > $max_col_count ? $col_count : $max_col_count;
        $row_count++;
      }

      fclose($handle);

      $tablefield['rebuild']['count_cols'] = $max_col_count;
      $tablefield['rebuild']['count_rows'] = $row_count;

      drupal_set_message($this->t('Successfully imported @file', array('@file' => $file_upload->getClientOriginalName())));

      return $tablefield;
    }

    drupal_set_message($this->t('There was a problem importing @file.', array('@file' => $file_upload->getClientOriginalName())));
    return FALSE;
  }

  function validateTablefield(array &$element, FormStateInterface &$form_state, array $form) {
    if ($element['#required'] && $form_state->getTriggeringElement()['#type'] == 'submit') {
      $items = new FieldItemList($this->fieldDefinition);
      $this->extractFormValues($items, $form, $form_state);
      if (!$items->count()) {
        $form_state->setError($element, t('!name field is required.', array('!name' => $this->fieldDefinition->getLabel())));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element[0];
  }

}
