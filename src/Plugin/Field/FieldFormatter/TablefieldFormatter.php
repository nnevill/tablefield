<?php

/**
 * @file
 * Contains \Drupal\tablefield\Plugin\Field\FieldFormatter\TablefieldFormatter.
 */

namespace Drupal\tablefield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Component\Utility\String;
use Drupal\tablefield\Utility\Tablefield;

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

    $field = $items[0]->getFieldDefinition();
    $field_name = $field->getName();
    $field_settings = $field->getSettings();

    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();


    $elements = array();
  
    foreach ($items as $delta => $table) {
  
      // Rationalize the stored data
      if (!empty($table->tablefield)) {
        $tabledata = Tablefield::rationalizeTable($table->tablefield);
      }
  
      // Run the table through input filters
      if (isset($tabledata)) {
        if (!empty($tabledata)) {
          foreach ($tabledata as $row_key => $row) {
            foreach ($row as $col_key => $cell) {
              if (!empty($table->format)) {
                $tabledata[$row_key][$col_key] = array('data' => check_markup($cell, $table->format), 'class' => array('row_' . $row_key, 'col_' . $col_key));
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

        // Theme the table for display
        $render_array = array(
          '#theme' => 'tablefield_view',
          '#header' => $header,
          '#rows' => $tabledata,
          '#delta' => $delta,
          '#export' => $field_settings['export'],
          '#entity_type' => $entity_type,
          '#entity_id' => $entity_id,
          '#field_name' => $field_name,
          '#langcode' => $items->getLangcode(),
        );
        $elements[$delta] = $render_array;
      }
  
    }
    return $elements;
  }

}
