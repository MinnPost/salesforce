<?php

/**
 * Salesforce Mapping UI controller.
 */
class SalesforceMappingUIController extends EntityDefaultUIController {

  /**
   * Overrides hook_menu() defaults.
   */
  public function hook_menu() {
    $items = parent::hook_menu();
    $items[$this->path]['description'] = 'Manage Salesforce mappings';
    return $items;
  }

  /**
   * Overrides EntityDefaultUIController::overviewForm().
   */
  public function overviewForm($form, &$form_state) {

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->entityType);

    if ($this->overviewPagerLimit) {
      $query->pager($this->overviewPagerLimit);
    }

    $results = $query->execute();

    $ids = isset($results[$this->entityType]) ? array_keys($results[$this->entityType]) : array();
    $entities = $ids ? entity_load($this->entityType, $ids) : array();

    $rows = array();
    foreach ($entities as $entity) {
      $id = entity_id($this->entityType, $entity);
      $weight = isset($form_state['values']['table'][$id]['weight']) ? $form_state['values']['table'][$id]['weight'] : $entity->weight;
      $rows[$id]['#row'] = $this->overviewTableRow(NULL, $id, $entity);
      $rows[$id]['#weight'] = $weight;
      $rows[$id]['weight'] = array(
        '#type' => 'weight',
        '#delta' => 30,
        '#default_value' => $weight,
      );
    }

    $form['table'] = $rows + array(
      '#theme' => 'salesforce_mapping_overview_tabledrag_form',
      '#tree' => TRUE,
      '#header' => $this->overviewTableHeaders(NULL, $rows),
      '#entity_type' => $this->entityType,
    );

    // Only show the save button when there are entities in the list.
    if (!empty($rows)) {
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
      );
    }

   return $form;
  }

  /**
   * Overrides EntityDefaultUIController::overviewFormSubmit().
   */
  public function overviewFormSubmit($form, &$form_state) {
    // Update entity weights.
    if (!empty($form_state['values']['table'])) {
      $entities = entity_load($this->entityType, array_keys($form_state['values']['table']));
      foreach ($entities as $key => $entity) {
        $id = entity_id($this->entityType, $entity);
        $entity->weight = $form_state['values']['table'][$id]['weight'];
        entity_save($this->entityType, $entities[$key]);
      }
    }
  }

  /**
   * Overrides EntityDefaultUIController::overviewTableHeaders().
   */
  protected function overviewTableHeaders($conditions, $rows, $additional_header = array()) {
    return parent::overviewTableHeaders($conditions, $rows, array(t('Weight')));
  }

}

/**
 * Returns the rendered tabledrag form for the entity overview listing.
 *
 * @ingroup themeable
 */
function theme_salesforce_mapping_overview_tabledrag_form($variables) {
  drupal_add_tabledrag('entity-ui-overview-form', 'order', 'sibling', 'entity-ui-weight');
  $form = $variables['form'];
  $rows = array();
  foreach (element_children($form) as $key) {
    $form[$key]['weight']['#attributes']['class'] = array('entity-ui-weight');
    $row = $form[$key]['#row'];
    // Replace the weight   column with the dropdown.
    $row['weight'] = array('data' => $form[$key]['weight']);
    $rows[$key] = array('data' => $row, 'class' => array('draggable'));
  }
  $render = array(
    '#theme' => 'table',
    '#header' => $form['#header'],
    '#rows' => $rows,
    '#empty' => t('None.'),
    '#attributes' => array('id' => 'entity-ui-overview-form'),
  );
  return drupal_render($render);
}