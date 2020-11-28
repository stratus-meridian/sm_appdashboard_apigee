<?php

namespace Drupal\sm_appdashboard_apigee_rules\Event;

use Apigee\Edge\Api\Management\Entity\App;
use Symfony\Component\EventDispatcher\Event;

/**
 * AppdashboardStatusApproved event when apps status getting approved.
 *
 * @package Drupal\sm_appdashboard_apigee_rules\Event
 */
class AppdashboardStatusApprovedEvent extends Event {

  /**
   * Event name.
   */
  const APP_STATUS_APPROVED = 'sm_appdashboard_apps_status_approved';

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
  public function __construct(App $app_entity) {
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
    return "Appdashborad event when app status getting approved.";
  }

}
