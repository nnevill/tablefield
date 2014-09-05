<?php

/**
 * @file
 * Contains \Drupal\tablefield\Plugin\Field\FieldWidget\TablefieldWidget.
 */

namespace Drupal\tablefield\Plugin\Field\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;

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
    //$default_value = $items[$delta]->getFieldDefinition()->default_value
  
    $element['#type'] = 'tablefield';
    $element['#attached']['css'][drupal_get_path('module', 'tablefield') . '/tablefield.css'] = array();
    $form['#attributes']['enctype'] = 'multipart/form-data';
  
    // Establish a list of saved/submitted/default values
    if (isset($form_state['clicked_button']['#value']) && $form_state['clicked_button']['#name'] == 'tablefield_rebuild_' . $field['field_name'] . '_' . $delta) {
      // Rebuilding table rows/cols
      $default_value = tablefield_rationalize_table($form_state['tablefield_rebuild'][$field['field_name']][$langcode][$delta]['tablefield']);
    }
    elseif (isset($form_state['clicked_button']['#value']) && $form_state['clicked_button']['#name'] == 'tablefield-import-button-' . $field['field_name'] . '-' . $delta) {
      // Importing CSV data
      tablefield_import_csv($form, $form_state);
      $default_value = tablefield_rationalize_table($form_state['input'][$field['field_name']][$langcode][$delta]['tablefield']);
    }
    elseif ($form_state['submitted'] && isset($items[$delta]) && isset($items[$delta]->tablefield)) {
      // A form was submitted
      $default_value = tablefield_rationalize_table($items[$delta]->tablefield);
    }
    elseif (isset($items[$delta]->value['tablefield'])) {
      // Default from database (saved node)
// @TODO this loads the default for new nodes in D8 now, the else may be unnecessary.
      //$default_value = tablefield_rationalize_table(unserialize($items[$delta]->value));
      $default_value = tablefield_rationalize_table($items[$delta]->value['tablefield']);
    }
    else {
      // After the first table, we don't want to populate the values in the table
// @TODO deal with multiple default values.
      //if ($delta > 0) {
        //tablefield_delete_table_values($default_value[0]['tablefield']);
      //}
  
      // Get the widget default value
      //if(!empty($form_state['input'][$field['field_name']][$langcode][$delta]['tablefield'])) {
        //$default_value = tablefield_rationalize_table($form_state['input'][$field['field_name']][$langcode][$delta]['tablefield']);
      //} else {
        $default_value = tablefield_rationalize_table(unserialize($items[$delta]->value));
      //}
  
      $default_count_cols = isset($items[0]->tablefield['rebuild']['count_cols']) ? $items[0]->tablefield['rebuild']['count_cols'] : 5;
      $default_count_rows = isset($items[0]->tablefield['rebuild']['count_cols']) ? $items[0]->tablefield['rebuild']['count_cols'] : 5;
    }
  
    if (!empty($instance['description'])) {
      $help_text = $instance['description'];
    }
    else {
      $help_text = t('The first row will appear as the table header. Leave the first row blank if you do not need a header.');
    }
  
    $element['tablefield'] = array(
      '#title' => $element['#title'],
      '#description' => Xss::filterAdmin($help_text),
      '#attributes' => array('id' => 'form-tablefield-' . $field['field_name'] . '-' . $delta, 'class' => array('form-tablefield')),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => FALSE,
      '#prefix' => '<div id="tablefield-' . $field['field_name'] . '-' . $delta . '-wrapper">',
      '#suffix' => '</div>',
    );
  
    // Give the fieldset the appropriate class if it is required
    if ($element['#required']) {
      $element['tablefield']['#title'] .= ' <span class="form-required" title="' . t('This field is required') . '">*</span>';
    }
  
    $path_args = explode('/', current_path());
    // Check if the current page is admin.
    if (\Drupal::service('router.admin_context')->isAdminRoute(\Drupal::routeMatch()->getRouteObject())) {
      $element['tablefield']['#description'] = t('This form defines the table field defaults, but the number of rows/columns and content can be overridden on a per-node basis. The first row will appear as the table header. Leave the first row bland if you do not need a header.');
    }
  
    // Determine how many rows/columns are saved in the data
    if (!empty($default_value)) {
      $count_rows = count($default_value);
      $count_cols = 0;
      foreach ($default_value as $row) {
        $temp_count = count($row);
        if ($temp_count > $count_cols) {
          $count_cols = $temp_count;
        }
      }
    }
    else {
      $count_rows = count($default_value);
      $count_cols = isset($default_count_cols) ? $default_count_cols : 0;
      $count_rows = isset($default_count_rows) ? $default_count_rows : 0;
    }
  
    // Override the number of rows/columns if the user rebuilds the form.
    if (isset($form_state['clicked_button']['#value']) && $form_state['clicked_button']['#name'] == 'tablefield_rebuild_' . $field['field_name'] . '_' . $delta) {
      $count_cols = $form_state['tablefield_rebuild'][$field['field_name']][$langcode][$delta]['tablefield']['rebuild']['count_cols'];
      $count_rows = $form_state['tablefield_rebuild'][$field['field_name']][$langcode][$delta]['tablefield']['rebuild']['count_rows'];
  
      drupal_set_message(t('Table structure rebuilt.'), 'status', FALSE);
    }
  
    // Render the form table
    $element['tablefield']['a_break'] = array(
      '#markup' => '<table>',
    );

    for ($i = 0; $i < $count_rows; $i++) {
      $zebra = $i % 2 == 0 ? 'even' : 'odd';
      $element['tablefield']['b_break' . $i] = array(
        '#markup' => '<tr class="tablefield-row-' . $i . ' ' . $zebra . '">',
      );
      for ($ii = 0; $ii < $count_cols; $ii++) {
        $instance_default = isset($default_value[$delta]['tablefield']["cell_{$i}_{$ii}"]) ? $default_value[$delta]['tablefield']["cell_{$i}_{$ii}"] : array();
        if (!empty($instance_default) && !empty($field['settings']['lock_values']) && $arg0 != 'admin') {
          // The value still needs to be send on every load in order for the
          // table to be saved correctly.
          $element['tablefield']['cell_' . $i . '_' . $ii] = array(
            '#type' => 'value',
            '#value' => $instance_default,
          );
          // Display the default value, since it's not editable.
          $element['tablefield']['cell_' . $i . '_' . $ii . '_display'] = array(
            '#type' => 'item',
            '#title' => $instance_default,
            '#prefix' => '<td style="width:' . floor(100/$count_cols) . '%">',
            '#suffix' => '</td>',
          );
        }
        else {
          $cell_default = isset($default_value[$i][$ii]) ? $default_value[$i][$ii] : '';
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
    if (isset($field['settings']['restrict_rebuild']) && $field['settings']['restrict_rebuild'] && !user_access('rebuild tablefield')) {
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
          '#value' => t('Rebuild Table'),
        ),
      );
    }
    else {
      $element['tablefield']['rebuild'] = array(
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => t('Change number of rows/columns.'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $element['tablefield']['rebuild']['count_cols'] = array(
        '#title' => t('How many Columns'),
        '#type' => 'textfield',
        '#size' => 5,
        '#prefix' => '<div class="clearfix">',
        '#suffix' => '</div>',
        '#value' => $count_cols,
      );
      $element['tablefield']['rebuild']['count_rows'] = array(
        '#title' => t('How many Rows'),
        '#type' => 'textfield',
        '#size' => 5,
        '#prefix' => '<div class="clearfix">',
        '#suffix' => '</div>',
        '#value' => $count_rows,
      );
      $element['tablefield']['rebuild']['rebuild'] = array(
        '#type' => 'button',
        '#validate' => array(),
        '#limit_validation_errors' => array(),
        '#executes_submit_callback' => TRUE,
        '#submit' => array('tablefield_rebuild_form'),
        '#value' => t('Rebuild Table'),
        '#name' => 'tablefield_rebuild_' . $field['field_name'] . '_' . $delta,
        '#attributes' => array(
          'class' => array('tablefield-rebuild'),
        ),
        '#ajax' => array(
          'callback' => 'tablefield_rebuild_form_ajax',
          'wrapper' => 'tablefield-' . $field['field_name'] . '-' . $delta . '-wrapper',
          'effect' => 'fade',
        ),
      );
    }
  
    // Allow the user to import a csv file
    $element['tablefield']['import'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => t('Import from CSV'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $element['tablefield']['import']['tablefield_csv_' . $field['field_name'] . '_' . $delta] = array(
      '#name' => 'files[' . $field['field_name'] . '_' . $delta . ']',
      '#title' => 'File upload',
      '#type' => 'file',
    );
  
    $element['tablefield']['import']['rebuild_' . $field['field_name'] . '_' . $delta] = array(
      '#type' => 'button',
      '#validate' => array(),
      '#limit_validation_errors' => array(),
      '#executes_submit_callback' => TRUE,
      '#submit' => array('tablefield_rebuild_form'),
      '#value' => t('Upload CSV'),
      '#name' => 'tablefield-import-button-' . $field['field_name'] . '-' . $delta,
      '#attributes' => array(
        'class' => array('tablefield-rebuild'),
        //'id' => 'tablefield-import-button-' . $field['field_name'] . '-' . $delta,
      ),
      '#ajax' => array(
        'callback' => 'tablefield_rebuild_form_ajax',
        'wrapper' => 'tablefield-' . $field['field_name'] . '-' . $delta . '-wrapper',
        'effect' => 'fade',
        'event' => 'click'
      ),
    );
  
  
    // Allow the user to select input filters
    if (!empty($field['settings']['cell_processing'])) {
      $element['#base_type'] = $element['#type'];
      $element['#type'] = 'text_format';
      $element['#format'] = isset($items[$delta]->format) ? $items[$delta]->format : NULL;
    }
  
    return array('value' => $element);
  }

}
