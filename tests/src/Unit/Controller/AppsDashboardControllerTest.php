<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @group sm_appdashboard_apigee
 */
class AppsDashboardControllerTest extends UnitTestCase {

  protected $prophecy;


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->prophecy = $this->prophesize('Drupal\sm_appdashboard_apigee\AppsDashboardStorageService');
  }

  /**
   * Test the list apps.
   */
  public function testListApps() {
    $labels = $this->prophecy->labels()->willReturn(['labelDisplayName' => 'App Display Name', 'labelDisplayName1' => 'App Display Name1']);
    $this->assertNotEmpty($labels);
  }
}