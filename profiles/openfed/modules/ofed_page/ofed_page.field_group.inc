<?php
/**
 * @file
 * ofed_page.field_group.inc
 */

/**
 * Implements hook_field_group_info().
 */
function ofed_page_field_group_info() {
  $field_groups = array();

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_ofed_page_page_meta|node|ofed_page|form';
  $field_group->group_name = 'group_ofed_page_page_meta';
  $field_group->entity_type = 'node';
  $field_group->bundle = 'ofed_page';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Page metadata',
    'weight' => '5',
    'children' => array(
      0 => 'field_ofed_page_pubdate',
      1 => 'field_ofed_page_update',
      2 => 'field_ofed_page_update_force',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Page metadata',
      'instance_settings' => array(
        'required_fields' => 1,
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $field_groups[''] = $field_group;

  // Translatables
  // Included for use with string extractors like potx.
  t('Page metadata');

  return $field_groups;
}
