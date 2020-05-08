<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @group sm_appdashboard_apigee
 */
class AppsDashboardControllerTest extends UnitTestCase {

  /**
   * Appdashboard storage.
   * @var Drupal\sm_appdashboard_apigee\AppsDashboardStorage
   */
  protected $appsDashboardStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test the list apps.
   */
  public function testListApps() {

    $prophecy = $this->prophesize('Drupal\sm_appdashboard_apigee\AppsDashboardStorage');
    $labels = $prophecy->labels()->willReturn(['labelDisplayName' => 'App Display Name', 'labelDisplayName1' => 'App Display Name1']);
    print_r($labels);
    exit;

  }
}