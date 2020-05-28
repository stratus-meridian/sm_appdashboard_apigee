<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Unit;

use Drupal\sm_appdashboard_apigee\AppsDashboardStorageService;
use Drupal\Tests\UnitTestCase;

/**
 * @group sm_appdashboard_apigee
 */
class AppsDashboardControllerTest extends UnitTestCase {

  /**
   * The AppsDashboardStorageService under test.
   *
   * @var \Drupal\sm_appdashboard_apigee\AppsDashboardStorageService
   */
  protected $appsDashboardStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->appsDashboardStorage = $this->prophesize(AppsDashboardStorageService::class);
  }

  /**
   * Test the list apps.
   */
  public function testListApps() {
    $labels = $this->appsDashboardStorage->labels()
      ->willReturn([
        'labelDisplayName' => 'App Display Name',
        'labelDisplayName1' => 'App Display Name1'
      ]);

    $this->assertNotEmpty($labels, "Presence of Apps display name labels.");
  }
}