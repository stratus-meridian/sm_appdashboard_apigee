<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Unit;

use Drupal\Core\Form\FormBuilder;
use Drupal\sm_appdashboard_apigee\AppsDashboardStorageService;
use Drupal\sm_appdashboard_apigee\Controller\AppsDashboardController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
/**
 * @group sm_appdashboard_apigee
 */
class AppsDashboardControllerTest extends UnitTestCase {

  /**
   * The AppsDashboardController.
   *
   * @var \Drupal\sm_appdashboard_apigee\Controller\AppsDashboardController
   */
  protected $appsDashboardController;

  /**
 * The AppsDashboardStorageService under test.
 *
 * @var \Drupal\sm_appdashboard_apigee\AppsDashboardStorageService
 */
  protected $appsDashboardStorage;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
//    $this->appsDashboardController = $this->getMockBuilder('Drupal\sm_appdashboard_apigee\Controller\AppsDashboardController')
//      ->disableOriginalConstructor()
//      ->setMethods(['getCurrentRequest'])
//      ->getMock();
    //$this->appsDashboardController = $this->prophesize(AppsDashboardController::class);
    $this->appsDashboardStorage = $this->prophesize(AppsDashboardStorageService::class);
    $this->formBuilder = $this->prophesize(FormBuilder::class);
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->appsDashboardController = new AppsDashboardController(
      $this->appsDashboardStorage->reveal(),
      $this->formBuilder->reveal(),
      $this->requestStack->reveal()
    );

  }

//  /**
//   * Test the list apps on Empty search.
//   */
//  public function testListAppsOnEmptySearch() {
//    $requestObject = $this->prophesize(Request::class);
//    $requestObject->get()->willReturn('');
//    $this->requestStack->getCurrentRequest()->willReturn($requestObject);
//    $this->appsDashboardStorage->getAllAppDetails()->willReturn([]);
//    $this->assertEmpty($this->appsDashboardController->listApps(), 'Tset failed on the empty search');
//  }

  /**
   * Testing the translations.
   */
  public function testTranslations() {
//    $apps = $this->prophesize(AppsDashboardController::class);
//    $apps = $apps->reveal();
    $requestObject = $this->prophesize(Request::class);
    $requestObject->get()->willReturn('');
    $this->requestStack->getCurrentRequest()->willReturn($requestObject);
    $this->appsDashboardStorage->getAllAppDetails()->willReturn([]);
    $this->formBuilder->t()->willReturn('Tett');
    $this->assertEmpty($this->appsDashboardController->listApps(), 'Second test');

  }
}