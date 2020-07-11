<?php

namespace Drupal\Tests\sm_appdashboard_apigee\Unit\controller;

use Drupal\apigee_edge\Entity\DeveloperApp;
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
      ->setMethods(['t', 'formatDate', 'getUrlFromRoute'])
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

  /**
   * Test list apps functionality with single developer app details.
   */
  public function testListAppsOnSingleDeveloperAppDetails() {
    $requestObject = $this->prophesize(Request::class);
    $requestObject->get(Argument::any())->willReturn('');
    $this->requestStack->getCurrentRequest()->willReturn($requestObject);

    $labels = [
      ['data' => 'App Display Name', 'field' => 'fieldDisplayName'],
      ['data' => 'Developer Email', 'field' => 'fieldEmail'],
      ['data' => 'Company', 'field' => 'fieldCompany'],
      [
        'data' => 'Overall App Status',
        'field' => 'fieldStatus',
        'sort' => 'desc',
      ],
      ['data' => 'Active user in the site?', 'field' => 'fieldOnwerActive'],
      ['data' => 'App Date/Time Created', 'field' => 'fieldDateTimeCreated'],
      ['data' => 'App Date/Time Modified', 'field' => 'fieldDateTimeModified'],
      'labelOperations' => 'Operations',
    ];
    $this->appsDashboardStorage->labels()->willReturn($labels);

    $this->appsDashboardController->expects($this->any())->method('formatDate')->willReturn('May. 02, 2020 01:23 AM');
    $this->appsDashboardController->expects($this->any())->method('getUrlFromRoute')->will($this->onConsecutiveCalls('/view', '/edit'));

    $developerAppEntity = $this->prophesize(DeveloperApp::class);
    $developerAppEntity->getEntityTypeId()->willReturn('developer_app');
    $developerAppEntity->getOwner()->willReturn('');
    $developerAppEntity->getOwnerId()->willReturn('');
    $developerAppEntity->getName()->willReturn('helloApp');
    $developerAppEntity->getDisplayName()->willReturn('My test app');
    $developerAppEntity->getCreatedBy()->willReturn('accounts_apigee_admin@google.com');
    $developerAppEntity->getCreatedAt()->willReturn(NULL);
    $developerAppEntity->getlastModifiedAt()->willReturn(NULL);

    $this->appsDashboardStorage->getAllAppDetails()->willReturn(['123456' => $developerAppEntity]);

    $this->appsDashboardStorage->getOverallStatus(Argument::any())->willReturn('approved');
    $this->appsDashboardStorage->constructPager(Argument::any(), 10)->willReturnArgument(0);
    $this->appsDashboardStorage->constructSort(Argument::any(), $labels)->willReturnArgument(0);

    $result = [
      'search__apps_dashboard' => NULL,
      'table__apps_dashboard' => [
        '#type' => 'table',
        '#header' => $labels,
        '#rows' => [
          [
            'fieldDisplayName' => 'My test app [Internal Name: helloApp]',
            'fieldEmail' => 'accounts_apigee_admin@google.com',
            'fieldCompany' => '',
            'fieldStatus' => 'approved',
            'fieldOnwerActive' => 'no',
            'fieldDateTimeCreated' => 'May. 02, 2020 01:23 AM',
            'fieldDateTimeModified' => 'May. 02, 2020 01:23 AM',
            'actions' => [
              'data' => [
                '#type' => 'dropbutton',
                '#links' => [
                  '#view' => [
                    'title' => 'View',
                    'url' => '/view',
                  ],
                  '#edit' => [
                    'title' => 'Edit',
                    'url' => '/edit',
                  ],
                ],
              ],
            ],
          ],
        ],
        '#empty' => 'No data found',
      ],
      'pager__apps_dashboard' => [
        '#type' => 'pager',
      ],
    ];

    $this->assertEquals($result, $this->appsDashboardController->listApps(), 'Failed to render the single developer app.');
  }

  /**
   * Test list apps functionality with search on date and time.
   */
  public function testListAppsOnDateTimeSearch() {
    $requestObject = $this->prophesize(Request::class);
    $requestObject->get('search_type')->willReturn('date_time_created');
    $requestObject->get('search_datetime_from')->willReturn('May. 02, 2020 01:23 AM');
    $requestObject->get('search_datetime_to')->willReturn('May. 02, 2020 01:23 AM');
    $this->requestStack->getCurrentRequest()->willReturn($requestObject);

    $labels = [
      ['data' => 'App Display Name', 'field' => 'fieldDisplayName'],
      ['data' => 'Developer Email', 'field' => 'fieldEmail'],
      ['data' => 'Company', 'field' => 'fieldCompany'],
      [
        'data' => 'Overall App Status',
        'field' => 'fieldStatus',
        'sort' => 'desc',
      ],
      ['data' => 'Active user in the site?', 'field' => 'fieldOnwerActive'],
      ['data' => 'App Date/Time Created', 'field' => 'fieldDateTimeCreated'],
      ['data' => 'App Date/Time Modified', 'field' => 'fieldDateTimeModified'],
      'labelOperations' => 'Operations',
    ];
    $this->appsDashboardStorage->labels()->willReturn($labels);

    $this->appsDashboardController->expects($this->any())->method('formatDate')->willReturn('May. 02, 2020 01:23 AM');
    $this->appsDashboardController->expects($this->any())->method('getUrlFromRoute')->will($this->onConsecutiveCalls('/view', '/edit'));

    $developerAppEntity = $this->prophesize(DeveloperApp::class);
    $developerAppEntity->getEntityTypeId()->willReturn('developer_app');
    $developerAppEntity->getOwner()->willReturn('');
    $developerAppEntity->getOwnerId()->willReturn('');
    $developerAppEntity->getName()->willReturn('helloApp');
    $developerAppEntity->getDisplayName()->willReturn('My test app');
    $developerAppEntity->getCreatedBy()->willReturn('accounts_apigee_admin@google.com');
    $developerAppEntity->getCreatedAt()->willReturn(NULL);
    $developerAppEntity->getlastModifiedAt()->willReturn(NULL);

    $this->appsDashboardStorage->searchByDates(Argument::any(), Argument::any())->willReturn(['123456' => $developerAppEntity]);

    $this->appsDashboardStorage->getAllAppDetails()->willReturn(['123456' => $developerAppEntity]);

    $this->appsDashboardStorage->getOverallStatus(Argument::any())->willReturn('approved');
    $this->appsDashboardStorage->constructPager(Argument::any(), 10)->willReturnArgument(0);
    $this->appsDashboardStorage->constructSort(Argument::any(), $labels)->willReturnArgument(0);

    $result = [
      'search__apps_dashboard' => NULL,
      'table__apps_dashboard' => [
        '#type' => 'table',
        '#header' => $labels,
        '#rows' => [
          [
            'fieldDisplayName' => 'My test app [Internal Name: helloApp]',
            'fieldEmail' => 'accounts_apigee_admin@google.com',
            'fieldCompany' => '',
            'fieldStatus' => 'approved',
            'fieldOnwerActive' => 'no',
            'fieldDateTimeCreated' => 'May. 02, 2020 01:23 AM',
            'fieldDateTimeModified' => 'May. 02, 2020 01:23 AM',
            'actions' => [
              'data' => [
                '#type' => 'dropbutton',
                '#links' => [
                  '#view' => [
                    'title' => 'View',
                    'url' => '/view',
                  ],
                  '#edit' => [
                    'title' => 'Edit',
                    'url' => '/edit',
                  ],
                ],
              ],
            ],
          ],
        ],
        '#empty' => 'No data found',
      ],
      'pager__apps_dashboard' => [
        '#type' => 'pager',
      ],
    ];

    $this->assertEquals($result, $this->appsDashboardController->listApps(), 'Failed to render the search results.');

  }

}
