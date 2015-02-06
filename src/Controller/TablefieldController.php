<?php

/**
 * @file
 * Contains \Drupal\tablefield\Controller\TablefieldController.
 */

namespace Drupal\tablefield\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Drupal\tablefield\Utility\Tablefield;

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

    $entity = entity_load($entity_type, $entity_id);
    $table = $entity->{$field_name}[$delta]->value;//Tablefield::rationalizeTable($entity->{$field_name}[$delta]->value);
    $separator = \Drupal::config('tablefield.settings')->get('csv_separator');

    $response = new StreamedResponse();
    $response->setCallback(function() use ($table, $separator) {
      ob_clean();
      $handle = fopen('php://output', 'w+');
      if (!empty($table) && $handle) {
        foreach ($table as $row) {
          fputcsv($handle, $row, $separator);
        }
      }
      fclose($handle);
    });

    $response->setStatusCode(200);
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition','attachment; filename="'. $filename .'"');

    return $response;
  }

}
