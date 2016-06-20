<?php

/**
 * @file
 * These are the hooks that are invoked by the Salesforce core.
 *
 * Core hooks are typically called in all modules at once using
 * module_invoke_all().
 */

/**
 * @defgroup salesforce_hooks Hooks provided by Salesforce API
 * @{
 */

/**
 * Trigger action when first building the list of fieldmap types.
 */
function hook_salesforce_mapping_fieldmap_type() {

}

/**
 * Alter existing fieldmap types.
 *
 * @param array $fieldmap_type
 *   Array of fieldmap Salesforce types
 */
function hook_salesforce_mapping_fieldmap_type_alter($fieldmap_type) {

}

/**
 * Alter parameters mapped to a Salesforce object before syncing to Salesforce.
 *
 * @param array $params
 *   Associative array of key value pairs.
 * @param object $mapping
 *   Salesforce mapping object.
 * @param object $entity_wrapper
 *   EntityMetadataWrapper of entity being mapped.
 */
function hook_salesforce_push_params_alter(&$params, $mapping, $entity_wrapper) {

}

/**
 * Provide URIs manually for poorly-behaved entity types.
 *
 * Note that this hook is self-implemented in salesforce_mapping.module, where
 * User and Node are both initialized.
 *
 * @param array $entity_uris
 *   An array of items indexed as 'entity_type' => URI, where URI contains a
 *   $path index.
 */
function hook_salesforce_mapping_entity_uris_alter(&$entity_uris) {
  // For example:
  $entity_uris['node'] = array(
    'path' => 'node/',
  );
}

/**
 * Prevent push to SF for an entity for a given mapping. For example: mapping a
 * single Drupal object to multiple separate Salesforce objects, but only
 * syncing under certain conditions.
 *
 * @param string $entity_type
 *   The type of entity the push is for.
 * @param object $entity
 *   The entity object the push is for.
 * @param int $sf_sync_trigger
 *   Constant for the Drupal operation that triggered the sync.
 * @param SalesforceMapping $mapping
 *   Salesforce mapping object for which to allow/disallow sync.
 *
 * @return bool
 *   FALSE if the entity should not be synced to Salesforce for this mapping/sync trigger.
 */
function hook_salesforce_push_entity_allowed($entity_type, $entity, $sf_sync_trigger, $mapping) {

}

/**
 * Alter the value being mapped to an entity property from a Salesforce object.
 *
 * @param string $value
 *   Salesforce field value.
 * @param array $field_map
 *   Associative array containing the field mapping in the form
 *   <code>
 *   'fieldmap_name' => array(
 *      'drupal_field' => array(
 *        'fieldmap_type' => 'property',
 *        'fieldmap_value' => 'first_name'
 *      ),
 *      'salesforce_field' => array()
 *   )
 *   </code>
 * @param object $sf_object
 *   Fully loaded Salesforce object
 */
function hook_salesforce_pull_entity_value_alter(&$value, $field_map, $sf_object) {

}

/**
 * Alter a SOQL select query before it is executed. For example, filter
 * target SObjects by a date value, or add an additional field to the query.
 *
 * @param SalesforceSelectQuery $query
 *   The query object to alter.
 *
 * @see includes/salesforce.select_query.inc
 */
function hook_salesforce_query_alter(SalesforceSelectQuery &$query) {
  if ($query->objectType == 'Contact') {
    $query->fields[] = 'Drupal_Field__c';
    $query->addCondition('Email', "''", '!=');
  }
}

/**
 * A salesforce push has succeeded: Implementations may wish to react, for
 * example, by alerting an administrator.
 *
 * @param string $op
 *   The salesforce operation: Create, Update, Upsert, or Delete
 * @param object $result
 *   The salesforce response
 * @param array $synced_entity
 *   Entity data for this push. This array has 3 keys
 *     'entity_wrapper': entity_metadata_wrapper() for the Drupal entity 
 *     'mapping_object': salesforce mapping object record, if it exists. 
 *       Otherwise null
 *     'queue_item': Drupal queue item corresponding to this push attempt
 */
function hook_salesforce_push_success($op, $result, $synced_entity) {
  
}

/**
 * A salesforce push has failed: Implementations may wish to react, for
 * example, by logging the failure or alerting an administrator.
 *
 * @param string $op
 *   The salesforce operation: Create, Update, Upsert, or Delete
 * @param object $result
 *   The salesforce response
 * @param array $synced_entity
 *   Entity data for this push. This array has 3 keys
 *     'entity_wrapper': entity_metadata_wrapper() for the Drupal entity 
 *     'mapping_object': salesforce mapping object record, if it exists. 
 *       Otherwise null
 *     'queue_item': Drupal queue item corresponding to this push attempt
 */
function hook_salesforce_push_fail($op, $result, $synced_entity) {
  
}

/**
 * Act on an entity just before it is saved by a salesforce pull operation.
 * Implementations should throw a SalesforcePullException to prevent the pull.
 *
 * @param $entity
 *   The Drupal entity object.
 * @param array $sf_object
 *   The Salesforce query result array.
 * @param SalesforceMapping $sf_object
 *   The Salesforce Mapping being used to pull this record
 *
 * @throws SalesforcePullException
 */
function hook_salesforce_pull_entity_presave($entity, $sf_object, $sf_mapping) {
  if (!some_entity_validation_mechanism($entity)) {
    throw new SalesforcePullException('Refused to pull invalid entity.');
  }
  // Set a fictional property using a fictional Salesforce result object.
  $entity->example_property = $sf_object['Lookup__r']['Data__c'];
}

/**
 * Act on an entity after it is inserted by a salesforce pull operation.
 * Implementations may throw SalesforcePullException to prevent updating of the
 * Salesforce Mapping Object, but the entity will already have been saved.
 *
 * @param $entity
 *   The Drupal entity object.
 * @param array $sf_object
 *   The SObject from the pull query (as an array).
 * @param SalesforceMapping $sf_object
 *   The Salesforce Mapping being used to pull this record
 *
 * @throws SalesforcePullException
 */
function hook_salesforce_pull_entity_insert($entity, $sf_object, $sf_mapping) {
  // Insert the new entity into a fictional table of all Salesforce-sourced
  // entities.
  $type = $sf_mapping->drupal_entity_type;
  $info = entity_get_info($type);
  list($id) = entity_extract_ids($type, $entity);
  db_insert('example_sf_entity')
    ->fields(array(
      'type' => $type,
      'id' => $id,
      'sf_name' => $sf_object['Name'],
      'created' => REQUEST_TIME,
      'updated' => REQUEST_TIME,
    ))
    ->execute();
}

/**
 * Act on an entity after it is updated by a salesforce pull operation.
 * Implementations may throw SalesforcePullException to prevent updating of the
 * Salesforce Mapping Object, but the entity will already have been saved.
 *
 * @param $entity
 *   The Drupal entity object.
 * @param array $sf_object
 *   The SObject from the pull query (as an array).
 * @param SalesforceMapping $sf_object
 *   The Salesforce Mapping being used to pull this record
 *
 * @throws SalesforcePullException
 */
function hook_salesforce_pull_entity_update($entity, $sf_object, $sf_mapping) {
  // Update the entity's entry in a fictional table of all Salesforce-sourced
  // entities.
  $type = $sf_mapping->drupal_entity_type;
  $info = entity_get_info($type);
  list($id) = entity_extract_ids($type, $entity);
  db_update('example_sf_entity')
    ->fields(array(
      'sf_name' => $sf_object['Name'],
      'updated' => REQUEST_TIME,
    ))
    ->condition('type', $type)
    ->condition('id', $id)
    ->execute();
}

/**
 * @} salesforce_hooks
 */
