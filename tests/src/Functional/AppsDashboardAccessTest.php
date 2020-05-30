<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the modules is correctly and App dashboard page is accessible.
 *
 * @group sm_appdashboard_apigee
 */
class AppsDashboardAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'sm_appdashboard_apigee',
    'apigee_edge',
    'apigee_edge_teams',
  ];

  /**
   * A test user with permission to access the Appdashboard.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $key = \Drupal::config('key.key.apigee_edge_connection_default');

    // Create and log in an administrative apigee edge user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer apigee edge',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test to access the apps dashboard page.
   */
   public function testAppsDashboardPageAccess() {
     $t = '';
     $this->drupalGet('/admin/config/apigee-edge/settings');
     $this->assertSession()->statusCodeEquals(200);
   }
}