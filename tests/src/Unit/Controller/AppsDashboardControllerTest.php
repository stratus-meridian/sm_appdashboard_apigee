<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Unit;

use Drupal\Core\Form\FormBuilder;
use Drupal\sm_appdashboard_apigee\AppsDashboardStorageService;
use Drupal\sm_appdashboard_apigee\Controller\AppsDashboardController;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\sm_appdashboard_apigee\Controller\AppsDashboardController
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
    $this->appsDashboardStorage = $this->prophesize(AppsDashboardStorageService::class);
    $this->formBuilder = $this->prophesize(FormBuilder::class);
    $this->requestStack = $this->prophesize(RequestStack::class);
    // Mock the Apps Dashboard controller object with t function.
    $this->appsDashboardController = $this->getMockBuilder(AppsDashboardController::class)
      ->setConstructorArgs([
        $this->appsDashboardStorage->reveal(),
        $this->formBuilder->reveal(),
        $this->requestStack->reveal(),
      ])
      ->setMethods(['t'])
      ->getMock();
    // Define the t function to return the same value as parameter.
    $this->appsDashboardController->expects($this->any())->method('t')->will($this->returnArgument(0));
  }

  /**
   * Test the list apps on Empty Search, Empty labels and Empty App Details.
   */
  public function testListAppsOnEmpty() {
    $requestObject = $this->prophesize(Request::class);
    $requestObject->get(Argument::any())->willReturn('');
    $this->appsDashboardStorage->labels()->willReturn([]);
    $this->requestStack->getCurrentRequest()->willReturn($requestObject);
    $this->appsDashboardStorage->getAllAppDetails()->willReturn([]);
    $this->appsDashboardStorage->constructPager([], 10)->willReturn([]);
    $this->appsDashboardStorage->constructSort([], [])->willReturn([]);

    $result = [
      'search__apps_dashboard' => NULL,
      'table__apps_dashboard' => [
        '#type' => 'table',
        '#header' => [],
        '#rows' => [],
        '#empty' => 'No data found',
      ],
      'pager__apps_dashboard' => [
        '#type' => 'pager',
      ],
    ];

    $this->assertEquals($result, $this->appsDashboardController->listApps(), 'Test failed on the empty search');
  }

  /**
   * Test the view app on empty app type and app id.
   */
  public function testViewAppOnEmpty() {
    $this->appsDashboardStorage->getAppDetailsById('', '')->willReturn(NULL);
    $this->appsDashboardStorage->getApiProducts()->willReturn([]);
    $result = [
      'details__app_details' => [
        '#type' => 'details',
        '#title' => 'App Details',
        '#open' => TRUE,
        'table__app_details' => [
          '#type' => 'table',
          '#rows' => [],
        ],
      ],
      'details__api_products' => [
        '#type' => 'details',
        '#title' => 'API Products',
        '#open' => TRUE,
        'apiProducts' => [
          '#type' => 'table',
          '#rows' => [],
          '#attributes' => [
            'class' => [
              'table__view__apps_dashboard__api_products',
            ],
          ],
        ],
      ],
      'edit__action' => [
        '#type' => 'link',
        '#title' => 'Edit',
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
        '#url' => '',
      ],
      'list__action' => [
        '#type' => 'link',
        '#title' => 'Back',
        '#attributes' => [
          'class' => [
            'button',
          ],
        ],
        '#url' => '',
      ],
    ];
    $this->assertEquals($result, $this->appsDashboardController->viewApp('', ''));
  }

}
