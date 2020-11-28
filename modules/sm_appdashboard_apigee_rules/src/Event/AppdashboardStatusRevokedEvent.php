<?php

namespace Drupal\sm_appdashboard_apigee_rules\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Appdashboard event for rules module integration on apps getting revoked.
 *
 * @package Drupal\sm_appdashboard_apigee_rules\Event
 */
class AppdashboardStatusRevokedEvent extends Event {

  /**
   * Event name.
   */
  const APP_STATUS_REVOKED = 'sm_appdashboard_apps_status_revoked';

  /**
   * The apigee app entity.
   *
   * @var \Apigee\Edge\Api\Management\Entity\App
   */
  protected $appEntity;

  /**
   * AppdashboardEvent constructor.
   *
   * @param \Apigee\Edge\Api\Management\Entity\App $app_entity
   *   The app entity.
   */
  public function __construct($app_entity) {
    $this->appEntity = $app_entity;
  }

  /**
   * Returns the app entity.
   *
   * @return \Apigee\Edge\Api\Management\Entity\App
   *   The app details.
   */
  public function geAppEntity() {
    return $this->appEntity;
  }

  /**
   * Returns the Event description.
   *
   * @return string
   *   Event description.
   */
  public function myEventDescription() {
    return "Appdashborad event when apps getting revoked.";
  }

}
