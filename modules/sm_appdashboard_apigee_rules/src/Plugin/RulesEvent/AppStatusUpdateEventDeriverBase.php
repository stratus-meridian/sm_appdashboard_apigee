<?php

namespace Drupal\sm_appdashboard_apigee_rules\Plugin\RulesEvent;

use Drupal\apigee_edge\Entity\EdgeEntityTypeInterface;
use Drupal\apigee_edge_actions\Plugin\RulesEvent\EdgeEntityEventDeriverBase;
use Drupal\apigee_edge\Entity\AppInterface;

/**
 * Base deriver for Edge entity product events.
 */
abstract class AppStatusUpdateEventDeriverBase extends EdgeEntityEventDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes(): array {
    // Filter out non app entity types.
    // API Credential is not an entity type so we use App instead.
    return array_filter(parent::getEntityTypes(), function (EdgeEntityTypeInterface $entity_type) {
      return $entity_type->entityClassImplements(AppInterface::class);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(EdgeEntityTypeInterface $entity_type): array {
    $context = parent::getContext($entity_type);

    // The api_product entity type is not fieldable hence does not support typed
    // data. We have to add the attributes individually here.
    $context['api_product_name'] = [
      'type' => 'string',
      'label' => $this->t('Name'),
    ];
    $context['api_product_display_name'] = [
      'type' => 'string',
      'label' => $this->t('Display name'),
    ];

    return $context;
  }

}