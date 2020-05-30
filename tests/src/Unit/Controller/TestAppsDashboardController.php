<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Unit;

use Drupal\sm_appdashboard_apigee\Controller\AppsDashboardController;

class TestAppsDashboardController extends AppsDashboardController {

  /**
   * Overriding the parent::getCurrentRequest().
   */
  protected function getCurrentRequest($key = '') {
    return 'No search';
  }
}