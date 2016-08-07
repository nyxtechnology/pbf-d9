<?php

namespace Drupal\pbf\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Plugin implementation of the 'pbf' field type.
 *
 * @FieldType(
 *   id = "pbf",
 *   label = @Translation("Permissions by field"),
 *   description = @Translation("Permissions by field"),
 *   default_widget = "pbf_widget",
 *   default_formatter = "pbf_formatter_default",
 *   list_class = "\Drupal\pbf\Plugin\Field\PbfFieldItemList",
 * )
 */
class Pbf extends EntityReferenceItem {

  protected static $operations = [
    'grant_public' => 'Public',
    'grant_view' => 'Grant View',
    'grant_update' => 'Grant Update',
    'grant_delete' => 'Grant Delete',
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'priority' => 0,
      'user_method' => 'user',
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add operations properties.
    foreach (self::$operations as $key => $value) {
      $properties[$key] = DataDefinition::create('boolean')
        ->setLabel(new TranslatableMarkup('@operation access', ['@operation' => $key]));
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    // Add operations columns.
    foreach (self::$operations as $key => $value) {
      $column_value = [
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ];

      $schema['columns'][$key] = $column_value;
    }

    return $schema;

  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    // We support only user_role, node, taxonomy_term and user.
    $entity_type = \Drupal::entityManager()->getEntityTypeLabels();
    $options_supported = [
      'user_role' => 'user_role',
      'node' => 'node',
      'taxonomy_term' => 'taxonomy_term',
      'user' => 'user',
    ];
    $options = array_intersect_key($entity_type, $options_supported);

    $element['target_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type of item to reference'),
      '#options' => $options,
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $form_state->getFormObject()->getEntity();
    $entity_type = $field->getTargetEntityTypeId();

    // No need to display theses options that are only relevant on Node entity
    // type.
    if ($entity_type !== 'node') {
      return $form;
    }

    // Priority parameter has been removed in Drupal 8, and will not be used
    // except by using Node access priority Module. See
    // https://www.drupal.org/project/napriority
    $form['priority'] = array(
      '#type' => 'details',
      '#title' => $this->t('Permissions priority'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => array(array(get_class($this), 'formProcessMergeParent')),
    );
    $form['priority']['priority'] = array(
      '#type' => 'number',
      '#title' => $this->t('Priority'),
      '#description' => $this->t('The priority to apply on permissions. 
      If not sure about this, let the default priority to 0. If you have some 
      issues with the permissions set, because you use multiple modules which 
      handle node access, try to increase the priority applied to 
      the permissions. Priority will be only used if the module Node access 
      priority is installed. Permissions with the higher priority will be then 
      used.'),
      '#default_value' => $field->getSetting('priority') ? $field->getSetting('priority') : 0,

    );

    $target_entity_type_id = $field->getSetting('target_type');
    if ($target_entity_type_id == 'user') {
      $options = [
        'user' => $this->t('Grant access directly to users referenced'),
        'ref_user' => $this->t('Grant access directly to users referenced and 
         grant access to users who reference those users from the field 
         <em>@field_name</em> attached to user entity type',
         ['@field_name' => $field->getName()]),
      ];
      $form['user_method'] = array(
        '#type' => 'details',
        '#title' => $this->t('Handle permissions for users'),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#process' => array(array(get_class($this), 'formProcessMergeParent')),
      );
      $form['user_method']['user_method'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Choose method for grant access to users'),
        '#options' => $options,
        '#default_value' => $field->getSetting('user_method') ? $field->getSetting('user_method') : 'user',
      );
    }

    return $form;
  }

  /**
   * Return the list of operations available.
   *
   * @return array
   *   The list of operations.
   */
  public function getOperations() {
    $operations = [
      'grant_public' => $this->t('Public'),
      'grant_view' => $this->t('Grant View'),
      'grant_update' => $this->t('Grant Update'),
      'grant_delete' => $this->t('Grant Delete'),
    ];
    return $operations;
  }

  /**
   * Check if the field is tag as public.
   *
   * @return bool
   *   The field is tag or not as public.
   */
  public function isPublic() {
    if ($this->grant_public) {
      return TRUE;
    }
    return FALSE;
  }

}
