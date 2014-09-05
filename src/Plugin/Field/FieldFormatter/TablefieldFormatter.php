<?php

/**
 * @file
 * Contains \Drupal\tablefield\Plugin\Field\FieldFormatter\TablefieldFormatter.
 */

namespace Drupal\tablefield\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Component\Utility\String;

/**
 * Plugin implementation of the 'geostore_wkt' formatter.
 *
 * @FieldFormatter (
 *   id = "tablefield",
 *   label = @Translation("Tablular View"),
 *   field_types = {
 *     "tablefield"
 *   }
 * )
 */
class TablefieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $settings = $display['settings'];
    $formatter = $display['type'];
  
    foreach ($items as $delta => $table) {
  
      // Rationalize the stored data
      if (!empty($table->value)) {
        $tabledata = tablefield_rationalize_table(unserialize($table->value));
      }
      //elseif (!empty($table['value'])) {
// @TODO is this necesary?
        //$tabledata = tablefield_rationalize_table(unserialize($table['value']));
      //}
  
      // Run the table through input filters
      if (isset($tabledata)) {
        if (!empty($tabledata)) {
          foreach ($tabledata as $row_key => $row) {
            foreach ($row as $col_key => $cell) {
              if (!empty($table->format)) {
                $tabledata[$row_key][$col_key] = array('data' => check_markup($cell, $table['format']), 'class' => array('row_' . $row_key, 'col_' . $col_key));
              }
              else {
                $tabledata[$row_key][$col_key] = array('data' => String::checkPlain($cell), 'class' => array('row_' . $row_key, 'col_' . $col_key));
              }
            }
          }
        }
  
        // Pull the header for theming
        $header_data = array_shift($tabledata);
  
        // Check for an empty header, if so we don't want to theme it.
        $noheader = TRUE;
        foreach ($header_data as $cell) {
          if (strlen($cell['data']) > 0) {
            $noheader = FALSE;
            break;
          }
        }
  
        $header = $noheader ? NULL : $header_data;

// @TODO this needs to be solved for export to work.
$entity_info = $entity_id = NULL;
        //$entity_info = entity_get_info($entity_type);
        //$entity_id = !empty($entity_info['entity keys']['id']) ? $entity->{$entity_info['entity keys']['id']} : NULL;
  
        // Theme the table for display
        $render_array = array(
          '#theme' => 'tablefield_view',
          '#header' => $header,
          '#rows' => $tabledata,
          '#delta' => $delta,
          '#export' => isset($field['settings']['export']) ? $field['settings']['export'] : NULL,
          '#entity_type' => $entity_type,
          '#entity_id' => $entity_id,
          '#field_name' => $field['field_name'],
          '#langcode' => $langcode,
        );
        $element[$delta]['#markup'] = drupal_render($render_array);
      }
  
    }
    return $element;
  }

}
