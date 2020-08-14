<?php

namespace Drupal\sm_appdashboard_apigee_rules\Plugin\RulesEvent;

use Drupal\apigee_edge\Entity\EdgeEntityTypeInterface;

/**
 * Deriver for Edge entity add_product events.
 */
class AppStatusUpdateEventDeriver extends AppStatusUpdateEventDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel(EdgeEntityTypeInterface $entity_type): string {
    return $this->t('After updating the Api product status.');
  }

}