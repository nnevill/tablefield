<?php

/**
 * @file
 * Contains \Drupal\tablefield\Controller\TablefieldController.
 */

namespace Drupal\tablefield\Controller;

/**
 * Controller routines for tablefield routes.
 */
class TablefieldController {

  /**
   * Menu callback to export a table as a CSV.
   *
   * @param String $entity_type
   *  The type of entity, e.g. node.
   * @param String $entity_id
   *  The id of the entity.
   * @param String $field_name
   *  The machine name of the field to load.
   * @param String $langcode
   *  The language code specified.
   * @param String $delta
   *  The field delta to load.
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function exportCsv($entity_type, $entity_id, $field_name, $langcode, $delta) {
    $filename = sprintf('%s_%s_%s_%s_%s.csv', $entity_type, $entity_id, $field_name, $langcode, $delta);
    $uri = 'temporary://' . $filename;
  
    // Attempt to load the entity.
    $ids = array($entity_id);
    $entity = entity_load($entity_type, $ids);
    $entity = array_pop($entity);
  
    // Ensure that the data is available and that we can load a
    // temporary file to stream the data.
    if (isset($entity->{$field_name}[$langcode][$delta]['value']) && $fp = fopen($uri, 'w+')) {
      $table = tablefield_rationalize_table(unserialize($entity->{$field_name}[$langcode][$delta]['value']));
  
      // Save the data as a CSV file.
      foreach ($table as $row) {
        fputcsv($fp, $row, variable_get('tablefield_csv_separator', ','));
      }
  
      fclose($fp);
  
      // Add basic HTTP headers.
      $http_headers = array(
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Content-Length' => filesize($uri),
      );
  
      // IE needs special headers.
      if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $http_headers['Cache-Control'] = 'must-revalidate, post-check=0, pre-check=0';
        $http_headers['Pragma'] = 'public';
      }
      else {
        $http_headers['Pragma'] = 'no-cache';
      }
  
      // Stream the download.
      file_transfer($uri, $http_headers);
    }
  
    // Something went wrong.
    drupal_add_http_header('Status', '500 Internal Server Error');
    print t('Error generating CSV.');
    drupal_exit();
  }

}
