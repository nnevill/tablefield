<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Textarea.
 */

namespace Drupal\tablefield\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a form element for input of multiple-line text.
 *
 * @FormElement("tablefield")
 */
class Tablefield extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#cols' => 5,
      '#rows' => 5,
      '#lock' => FALSE,
      '#locked_cells' => array(),
      '#rebuild' => FALSE,
      '#import' => FALSE,
      '#process' => array(
        array($class, 'processTablefield'),
      ),

      '#theme_wrappers' => array('form_element'),
    );
  }


  /**
   * Processes a checkboxes form element.
   */
  public static function processTablefield(&$element, FormStateInterface $form_state, &$complete_form) {
    
    $value = is_array($element['#value']) ? $element['#value'] : array();
    $field_name = $element['#parents'][0];
    $delta = $element['#delta'];
    
    // check what triggered this
    // we might need to rebuild or to import
    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield_rebuild_' . $field_name . '_' . $delta) {
      //$value = $form_state->getValue([$field_name, $delta]);

      $element['#rows'] = $value['tablefield']['rebuild']['rows'];
      $element['#cols'] = $value['tablefield']['rebuild']['cols'];

      drupal_set_message(t('Table structure rebuilt.'), 'status', FALSE);
    }
    elseif (isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield_import_' . $field_name . '-' . $delta) {
      // Import CSV
      $imported_tablefield = self::importCsv($field_name . '_' . $delta);

      if ($imported_tablefield) {

        $element['#rows'] = $imported_tablefield['rebuild']['rows'];
        $element['#cols'] = $imported_tablefield['rebuild']['cols'];

        $parents = array_slice($triggering_element['#parents'], 0, -2, TRUE);
        $form_state->setValue($parents, $imported_tablefield);

        // Unfortunately no nice cherry-pick setUserInput available, have to do it long way
        $input = $form_state->getUserInput();
        NestedArray::setValue($input, $parents, $imported_tablefield);
        $form_state->setUserInput($input);

        $value = $imported_tablefield['table'];
      }
    }
    
    $element['#tree'] = TRUE;

    // @TODO: could a twig template be used for what's below?

    $element['tablefield'] = array(
      '#attributes' => array('id' => 'form-tablefield-' . $field_name . '-' . $delta, 'class' => array('form-tablefield')),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#prefix' => '<div id="tablefield-' . $field_name . '-' . $delta . '-wrapper">',
      '#suffix' => '</div>',
    );

    $element['tablefield']['table'] = array(
      '#type' => 'markup',
      '#prefix' => '<table>',
      '#suffix' => '</table>',
    );

    $cols = isset($element['#cols']) ? $element['#cols'] : 5;
    $rows = isset($element['#rows']) ? $element['#rows'] : 5;

    for ($i = 0; $i < $rows; $i++) {
      $zebra = $i % 2 == 0 ? 'even' : 'odd';
      $element['tablefield']['table'][$i] = array(
        '#type' => 'markup',
        '#prefix' => '<tr class="tablefield-row-' . $i . ' ' . $zebra . '">',
        '#sufix' => '</tr>',
      );
      for ($ii = 0; $ii < $cols; $ii++) {

        if (!empty($element['#locked_cells'][$i][$ii]) && !empty($element['#lock'])) {
          $element['tablefield']['table'][$i][$ii] = array(
            '#type' => 'item',
            '#value' => $element['#locked_cells'][$i][$ii],
            '#title' => $element['#locked_cells'][$i][$ii],
            '#prefix' => '<td style="width:' . floor(100/$cols) . '%">',
            '#suffix' => '</td>',
          );
        }
        else {
          $cell_value = isset($value[$i][$ii]) ? $value[$i][$ii] : '';
          $element['tablefield']['table'][$i][$ii] = array(
            '#type' => 'textfield',
            '#maxlength' => 2048,
            '#size' => 0,
            '#attributes' => array(
              'id' => 'tablefield_' . $delta . '_cell_' . $i . '_' . $ii,
              'class' => array('tablefield-row-' . $i, 'tablefield-col-' . $ii),
              'style' => 'width:100%'
            ),
            '#default_value' => $cell_value,
            '#prefix' => '<td style="width:' . floor(100/$count_cols) . '%">',
            '#suffix' => '</td>',
          );
        }
      }
    }

    // If no rebuild, we pass along the rows/cols as a value.
    // Otherwise, we will provide form elements to specify the size and ajax rebuild.
    if (empty($element['#rebuild'])) {
      $element['tablefield']['rebuild'] = array (
        '#type' => 'value',
        '#tree' => TRUE,
        'cols' => array(
          '#type' => 'value',
          '#value' => $cols,
        ),
        'rows' => array(
          '#type' => 'value',
          '#value' => $rows,
        ),
      );
    }
    else {
      $element['tablefield']['rebuild'] = array(
        '#type' => 'details',
        '#tree' => TRUE,
        '#title' => t('Change number of rows/columns.'),
        '#open' => FALSE,
      );
      $element['tablefield']['rebuild']['cols'] = array(
        '#title' => t('How many Columns'),
        '#type' => 'textfield',
        '#size' => 5,
        '#prefix' => '<div class="clearfix">',
        '#suffix' => '</div>',
        '#default_value' => $cols,
      );
      $element['tablefield']['rebuild']['rows'] = array(
        '#title' => t('How many Rows'),
        '#type' => 'textfield',
        '#size' => 5,
        '#prefix' => '<div class="clearfix">',
        '#suffix' => '</div>',
        '#default_value' => $rows,
      );
      $element['tablefield']['rebuild']['rebuild'] = array(
        '#type' => 'button',
        '#value' => t('Rebuild Table'),
        '#name' => 'tablefield_rebuild_' . $field_name . '_' . $delta,
        '#attributes' => array(
          'class' => array('tablefield-rebuild'),
        ),
        '#ajax' => array(
          'callback' => 'Drupal\tablefield\Element\Tablefield::ajaxCallbackRebuild',
          'progress' => array('type' => 'throbber', 'message' => NULL),
          'wrapper' => 'tablefield-' . $field_name . '-' . $delta . '-wrapper',
          'effect' => 'fade',
        ),
      );
    }

    // Allow import of a csv file
    if (!empty($element['#import'])) {
      $element['tablefield']['import'] = array(
        '#type' => 'details',
        '#tree' => TRUE,
        '#title' => t('Import from CSV'),
        '#open' => FALSE,
      );
      $element['tablefield']['import']['csv'] = array(
        '#name' => 'files[' . $field_name . '_' . $delta . ']',
        '#title' => 'File upload',
        '#type' => 'file',
      );

      $element['tablefield']['import']['import'] = array(
        '#type' => 'button',
        '#value' => t('Upload CSV'),
        '#name' => 'tablefield_import_' . $field_name . '-' . $delta,
        '#attributes' => array(
          'class' => array('tablefield-rebuild'),
        ),
        '#ajax' => array(
          'callback' => 'Drupal\tablefield\Element\Tablefield::ajaxCallbackRebuild',
          'progress' => array('type' => 'throbber', 'message' => NULL),
          'wrapper' => 'tablefield-' . $field_name . '-' . $delta . '-wrapper',
          'effect' => 'fade',
        ),
      );
    }
    return $element;
  }

  /**
   * AJAX callback to rebuild the number of rows/columns.
   * The basic idea is to descend down the list of #parent elements of the
   * triggering_element in order to locate the tablefield inside of the $form
   * array. That is the element that we need to return.
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function ajaxCallbackRebuild(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // go as deep as 'tablefield' key, but stop there (two more keys follow)
    $parents = array_slice($triggering_element['#array_parents'], 0, -2, TRUE);
    $rebuild = NestedArray::getValue($form, $parents);

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
  private static function importCsv($form_field_name) {
    $file_upload = \Drupal::request()->files->get("files[$form_field_name]", NULL, TRUE);
    if (!empty($file_upload) && $handle = fopen($file_upload->getPathname(), 'r'))  {
      // Populate CSV values
      $tablefield = array();
      $max_cols = 0;
      $rows = 0;

      $separator = \Drupal::config('tablefield.settings')->get('tablefield_csv_separator');
      while (($csv = fgetcsv($handle, 0, $separator)) !== FALSE) {
        foreach ($csv as $value) {
          $tablefield['table'][$rows][] = $value;
        }
        $cols = count($csv);
        if ($cols > $max_cols) {
          $max_cols = $cols;
        }
        $rows++;
      }

      fclose($handle);

      $tablefield['rebuild']['cols'] = $max_cols;
      $tablefield['rebuild']['rows'] = $rows;

      drupal_set_message(t('Successfully imported @file', array('@file' => $file_upload->getClientOriginalName())));

      return $tablefield;
    }

    drupal_set_message(t('There was a problem importing @file.', array('@file' => $file_upload->getClientOriginalName())));
    return FALSE;
  }

}
