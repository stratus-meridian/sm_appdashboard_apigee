<?php

namespace Drupal\sm_appdashboard_apigee_rules\Event;

use Symfony\Component\EventDispatcher\Event;

class AppdashboardEvent extends Event {

  const APP_STATUS_CHANGE = 'sm_appdashboard_apps_status_change';

  /**
   * @var \Apigee\Edge\Api\Management\Entity\App
   */
  protected $appEntity;

  /**
   * AppdashboardEvent constructor.
   *
   * @param $app_entity
   *   The app entity.
   */
  public function __construct($app_entity) {
    $this->appEntity = $app_entity;
  }

  /**
   * @return \Apigee\Edge\Api\Management\Entity\App
   *   The app details.
   */
  public function geAppEntity() {
    return $this->appEntity;
  }

  /**
   * @return string
   *   Event description.
   */
  public function myEventDescription() {
    return "Appdashborad event for app status update.";
  }
}