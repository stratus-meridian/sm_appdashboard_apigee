<?php

namespace Drupal\sm_appdashboard_apigee\Controller;

/**
 * @file
 * Copyright (C) 2020  Stratus Meridian LLC.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Apps Dashboard view and list pages.
 */
class AppsDashboardController extends ControllerBase {

  /**
   * AppsDashboardStorageServiceInterface definition.
   *
   * @var Drupal\sm_appdashboard_apigee\AppsDashboardStorageServiceInterface
   */
  protected $appsDashboardStorage;

  /**
   * The Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * AppsDashboardController constructor.
   */
  public function __construct($appsDashboardStorage, $formBuilder, $requestStack) {
    $this->appsDashboardStorage = $appsDashboardStorage;
    $this->formBuilder = $formBuilder;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sm_appsdashboard_apigee.appsdashboard_storage'),
      $container->get('form_builder'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listApps() {
    // Define search logic.
    $searchType = $this->requestStack->getCurrentRequest()->get('search_type');

    if ($searchType == 'date_time_created' || $searchType == 'date_time_modified') {
      if ($this->requestStack->getCurrentRequest()->get('search_datetime_from') || $this->requestStack->getCurrentRequest()->get('search_datetime_to')) {
        $searchKey = [
          'from' => $this->requestStack->getCurrentRequest()->get('search_datetime_from'),
          'to' => $this->requestStack->getCurrentRequest()->get('search_datetime_to'),
        ];
      }
      else {
        $searchKey = NULL;
        $searchType = NULL;
      }
    }
    else {
      if ($this->requestStack->getCurrentRequest()->get('search')) {
        $searchKey = $this->requestStack->getCurrentRequest()->get('search');
      }
      else {
        $searchKey = NULL;
        $searchType = NULL;
      }
    }

    if (isset($searchKey) || isset($searchType)) {
      if ($searchType == 'date_time_created' || $searchType == 'date_time_modified') {
        $apps = $this->appsDashboardStorage->searchByDates($searchKey, $searchType);
      }
      else {
        $apps = $this->appsDashboardStorage->searchBy($searchKey, $searchType);
      }
    }
    else {
      // Retrieve Apps Details (Developer and Team Apps).
      $apps = $this->appsDashboardStorage->getAllAppDetails();
    }

    // Define Table Headers.
    $labelAppDetails = $this->appsDashboardStorage->labels();

    // Pass App Details into variables.
    $appDetails = [];

    foreach ($apps as $appKey => $app) {
      if ($app->getEntityTypeId() == 'developer_app') {
        // Set Developer Apps owner active data.
        $ownerEntity = $app->getOwner() ?? NULL;

        if ($ownerEntity) {
          $appOwnerActive = ($ownerEntity->get('status')->getValue()[0]['value'] == 1 ? $this->t('yes') : $this->t('no'));
        }
        else {
          $appOwnerActive = $this->t('no');
        }

        // Set Developer Apps email address data.
        if ($app->getOwnerId()) {
          if ($ownerEntity) {
            $appDeveloperEmail = ($ownerEntity->getEmail() ? $ownerEntity->getEmail() : '');
          }
        }
        else {
          $appDeveloperEmail = $app->getCreatedBy();
        }

        $appCompany = '';
      }
      else {
        // Set Team Apps company name.
        $appDeveloperEmail = '';
        $appCompany = $app->getCompanyName();
      }

      // Get App Overall Status.
      $appOverallStatus = $this->appsDashboardStorage->getOverallStatus($app);

      // Setup actions (dropdown).
      $route_parameters = [
        'apptype' => $app->getEntityTypeId(),
        'appid' => $appKey,
      ];
      $view_url = $this->getUrlFromRoute('apps_dashboard.view', $route_parameters);
      $edit_url = $this->getUrlFromRoute('apps_dashboard.edit', $route_parameters);

      $drop_button = [
        '#type' => 'dropbutton',
        '#links' => [
          '#view' => [
            'title' => $this->t('View'),
            'url' => $view_url,
          ],
          '#edit' => [
            'title' => $this->t('Edit'),
            'url' => $edit_url,
          ],
        ],
      ];

      // App Details array push to variables.
      array_push($appDetails, [
        'fieldDisplayName' => $app->getDisplayName() . ' [Internal Name: ' . $app->getName() . ']',
        'fieldEmail' => $appDeveloperEmail,
        'fieldCompany' => $appCompany,
        'fieldStatus' => $appOverallStatus,
        'fieldOnwerActive' => $appOwnerActive,
        'fieldDateTimeCreated' => $this->formatDate($app->getCreatedAt(), 'M. d, Y h:i A'),
        'fieldDateTimeModified' => $this->formatDate($app->getlastModifiedAt(), 'M. d, Y h:i A'),
        'actions' => [
          'data' => $drop_button,
        ],
      ]);
    }

    // Construct Pager.
    $appDetails = $this->appsDashboardStorage->constructPager($appDetails, 10);

    // Construct Table Sort.
    $appDetails = $this->appsDashboardStorage->constructSort($appDetails, $labelAppDetails);

    // Merge into one array variable.
    $arrApps = [
      'labelAppDetails' => $labelAppDetails,
      'appDetails' => $appDetails,
    ];

    $form = [
      'search__apps_dashboard' => $this->formBuilder->getForm('\Drupal\sm_appdashboard_apigee\Form\AppDetailsSearchForm'),
      'table__apps_dashboard' => [
        '#type' => 'table',
        '#header' => $arrApps['labelAppDetails'],
        '#rows' => $arrApps['appDetails'],
        '#empty' => $this->t('No data found'),
      ],
      'pager__apps_dashboard' => [
        '#type' => 'pager',
      ],
    ];

    return $form;
  }

  /**
   * Helper function to formatted date.
   *
   * @param object $dateObject
   *   The Date immutable object.
   * @param string $format_type
   *   The date format type.
   *
   * @return string
   *   The formatted date string.
   *
   * @codeCoverageIgnore
   */
  protected function formatDate($dateObject, $format_type) {
    return $dateObject->format($format_type);
  }

  /**
   * Helper function to get the url from route name.
   *
   * @param string $route_name
   *   The route name.
   * @param array $route_parameters
   *   Array with details of app type and app id.
   *
   * @return object
   *   The Url object.
   *
   * @codeCoverageIgnore
   */
  protected function getUrlFromRoute($route_name, array $route_parameters) {
    return Url::fromRoute($route_name, $route_parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function viewApp($apptype, $appid) {
    // Initializing the variables.
    $data = $data_apiProducts = [];
    $edit_url = $return_url = '';

    if (!isset($apptype) || !isset($appid)) {
      $this->messenger()->addError($this->t('There are errors encountered upon viewing the App Details.'));
      $path = Url::fromRoute('apps_dashboard.list', [])->toString();
      $response = new RedirectResponse($path);
      $response->send();
    }

    // Load App Deails.
    /* @var $app App*/
    $app = $this->appsDashboardStorage->getAppDetailsById($apptype, $appid);

    if (isset($app)) {
      if ($app->getEntityTypeId() == 'developer_app') {
        // Set Developer Apps owner active data.
        $ownerEntity = $app->getOwner();

        if ($ownerEntity) {
          $appOwnerActive = ($ownerEntity->get('status')->getValue()[0]['value'] == 1 ? $this->t('yes') : $this->t('no'));
        }
        else {
          $appOwnerActive = $this->t('no');
        }

        // Set Developer Apps email address data.
        if ($app->getOwnerId()) {
          if ($ownerEntity) {
            $appDeveloperEmail = ($ownerEntity->getEmail() ? $ownerEntity->getEmail() : '');
          }
        }
        else {
          $appDeveloperEmail = $app->getCreatedBy();
        }

        $appCompany = '';
      }
      else {
        // Set Team Apps company name.
        $appDeveloperEmail = '';
        $appCompany = $app->getCompanyName();
      }

      // Get App Overall Status.
      $appOverallStatus = $this->appsDashboardStorage->getOverallStatus($app);

      $data_apiProducts = [];

      $i = 1;
      foreach ($app->getCredentials() as $credential) {
        $data_apiProducts[$credential->id()] = [
          '#type' => 'fieldset',
          '#title' => 'Credential ' . $i++,
        ];
        $product_status = [];
        // Get App Credentials and API Products.
        foreach ($credential->getApiProducts() as $apiProduct) {
          $product_status[] = [
            [
              'data' => $apiProduct->getApiproduct(),
              'header' => TRUE,
            ],
            $apiProduct->getStatus(),
          ];
        }
        $data_apiProducts[$credential->id()]['products'] = [
          '#type' => 'table',
          '#rows' => $product_status,
          '#attributes' => [
            'class' => [
              'table__view__apps_dashboard__api_products',
            ],
          ],
        ];
      }

      // Plotting App Details into Table.
      $data = [
        [
          ['data' => 'App Type', 'header' => TRUE],
          $apptype,
        ],
        [
          ['data' => 'App Display Name', 'header' => TRUE],
          $app->getDisplayName(),
        ],
        [
          ['data' => 'Internal Name', 'header' => TRUE],
          $app->getName(),
        ],
        [
          ['data' => 'Developer Email Address', 'header' => TRUE],
          $appDeveloperEmail,
        ],
        [
          ['data' => 'Company', 'header' => TRUE],
          $appCompany,
        ],
        [
          ['data' => 'Overall App Status', 'header' => TRUE],
          $appOverallStatus,
        ],
        [
          ['data' => 'Active User in the site?', 'header' => TRUE],
          $appOwnerActive,
        ],
        [
          ['data' => 'App Date/Time Created', 'header' => TRUE],
          $app->getCreatedAt()->format('M. d, Y h:i A'),
        ],
        [
          ['data' => 'App Date/Time Modified', 'header' => TRUE],
          $app->getLastModifiedAt()->format('M. d, Y h:i A'),
        ],
        [
          ['data' => 'Modified by', 'header' => TRUE],
          $app->getLastModifiedBy(),
        ],
      ];

      $return_url = Url::fromRoute('apps_dashboard.list');
      $edit_url = Url::fromRoute('apps_dashboard.edit', [
        'apptype' => $app->getEntityTypeId(),
        'appid' => $appid,
      ]);

    }

    $display = [
      'details__app_details' => [
        '#type' => 'details',
        '#title' => $this->t('App Details'),
        '#open' => TRUE,
        'table__app_details' => [
          '#type' => 'table',
          '#rows' => $data,
        ],
      ],
      'details__api_products' => [
        '#type' => 'details',
        '#title' => $this->t('Credentials'),
        '#open' => TRUE,
        'app_credentials' => $data_apiProducts,
      ],
      'actions' => [
        'edit__action' => [
          '#type' => 'link',
          '#title' => $this->t('Edit'),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
          '#url' => $edit_url,
        ],
        'list__action' => [
          '#type' => 'link',
          '#title' => $this->t('Back'),
          '#attributes' => [
            'class' => [
              'button',
            ],
          ],
          '#url' => $return_url,
        ],
      ],
    ];

    return $display;
  }

}
