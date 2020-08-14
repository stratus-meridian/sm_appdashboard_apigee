<?php

namespace Drupal\sm_appdashboard_apigee_rules\Plugin\RulesEvent;

use Symfony\Component\EventDispatcher\Event;


class AppStatusUpdateEvent extends Event {

  const SUBMIT = 'event.app_status_update';

  protected $referenceID;

  public function __construct($referenceID)
  {
    $this->referenceID = $referenceID;
  }

  public function getReferenceID()
  {
    return $this->referenceID;
  }

  public function myEventDescription() {
    return "When Apigee app status getting updated from Apps Dashborad.";
  }

}