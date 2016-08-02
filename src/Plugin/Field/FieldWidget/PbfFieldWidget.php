<?php

/**
 * @file
 * The default Pbf field widget.
 */

namespace Drupal\pbf\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\user\UserInterface;


/**
 * Plugin implementation of the 'pbf_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "pbf_widget",
 *   label = @Translation("Permissions by field widget"),
 *   field_types = {
 *     "pbf"
 *   }
 * )
 */
class PbfFieldWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();

    if ($entity instanceof UserInterface) {
      return $element;
    }

    $item = $items[$delta];
    $operations = $item->getOperations();

    /** @var \Drupal\field\FieldConfigInterface $field_definition */
    $field_definition = $item->getFieldDefinition();
    $field_name = $field_definition->getName();

    foreach ($operations as $key => $label) {
      $element[$key] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => isset($item->{$key}) ? $item->{$key} : NULL,
        '#return_value' => 1,
        '#empty' => 0,
        '#weight' => 2,
      ];

      // We hide other $key than grant_public because these keys are not used
      // if grant_public is checked. With grant_public, we let standard
      // permissions apply on the node.
      if ($key != 'grant_public') {
        $element[$key]['#states'] = [
          'invisible' => [
            ':input[name="'. $field_name . '[' . $delta . '][grant_public]"]' => array('checked' => TRUE),
          ],
        ];
      }
    }

    $element['help'] = [
      '#type' => 'details',
      '#title' => 'Help about permissions',
      '#markup' => $this->t('The public checkbox checked means that standard 
      permissions will be applied. With this option checked you can simply
      reference an entity without any custom permissions applied to this current 
      node. 
      If you want to apply custom permissions for this node, permissions related 
      to the entity referenced, uncheck public option, 
      and then choose relevant permissions. If none of custom permissions are 
      checked, only the node\'s author will can access to the node.'),
      '#attributes' => ['class' => ['description', 'pbf-help']],
      '#weight' => 5,
    ];

    $element['#attached']['library'][] = 'pbf/widget';

    return $element;
  }

}
