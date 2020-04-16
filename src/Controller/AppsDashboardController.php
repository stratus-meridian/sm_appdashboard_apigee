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
use Drupal\sm_appdashboard_apigee\AppsDashboardStorage;
use Drupal\Core\Url;

/**
 * @file
 * Defines AppsDashboardController class.
 */
class AppsDashboardController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function listApps() {
    // Define Table Headers.
    $labelAppDetails = AppsDashboardStorage::labels();

    // Retrieve Apps Details (Developer and Team Apps).
    $apps = AppsDashboardStorage::getAllAppDetails();

    // Pass App Details into variables.
    $appDetails = [];

    foreach ($apps as $appKey => $app) {
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

      // Get App Credentials.
      $appCredentials = $app->getCredentials();

      // Get App Overall Status.
      $appOverallStatus = AppsDashboardStorage::getOverallStatus($app);

      // Setup actions (dropdown).
      $view_url = Url::fromRoute('apps_dashboard.view', [
        'apptype' => $app->getEntityTypeId(),
        'appid' => $appKey,
      ]);

      $edit_url = Url::fromRoute('apps_dashboard.edit', [
        'apptype' => $app->getEntityTypeId(),
        'appid' => $appKey,
      ]);

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
        'AppDisplayName' => $app->getDisplayName() . ' [Internal Name: ' . $app->getName() . ']',
        'AppDeveloperEmail' => $appDeveloperEmail,
        'AppCompany' => $appCompany,
        'AppStatus' => $appOverallStatus,
        'OwnerActive' => $appOwnerActive,
        'AppCreatedAt' => $app->getCreatedAt()->format('l, M. d, Y H:i'),
        'AppModifiedAt' => $app->getlastModifiedAt()->format('l, M. d, Y H:i'),
        'actions' => [
          'data' => $drop_button,
        ],
      ]);
    }

    // Merge into one array variable.
    $arrApps = [
      'labelAppDetails' => $labelAppDetails,
      'appDetails' => $appDetails,
    ];

    $form['table__apps_dashboard'] = [
      '#type' => 'table',
      '#header' => $arrApps['labelAppDetails'],
      '#rows' => $arrApps['appDetails'],
      '#empty' => $this->t('No data found'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewApp($apptype, $appid) {
    if (!isset($apptype) || !isset($appid)) {
      drupal_set_message(t('There are errors encountered upon viewing the App Details.'), 'error');
      return new RedirectResponse(Drupal::url('apps_dashboard.list'));
    }

    $appDetails = [];

    // Load App Deails.
    $app = AppsDashboardStorage::getAppDetailsById($apptype, $appid);

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

    // Get App Credentials and API Products.
    $appCredentials = $app->getCredentials();
    $apiProducts = AppsDashboardStorage::getApiProducts($app);

    // Get App Overall Status.
    $appOverallStatus = AppsDashboardStorage::getOverallStatus($app);

    $data_apiProducts = [];

    foreach ($apiProducts as $apiProduct) {
      $data_apiProducts[] = [
        [
          'data' => $apiProduct[0],
          'header' => TRUE,
        ],
        $apiProduct[1],
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
        $app->getCreatedAt()->format('l, M. d, Y H:i'),
      ],
      [
        ['data' => 'App Date/Time Modified', 'header' => TRUE],
        $app->getLastModifiedAt()->format('l, M. d, Y H:i'),
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

    $display = [
      'details__app_details' => [
        '#type' => 'details',
        '#title' => t('App Details'),
        '#open' => TRUE,
        'table__app_details' => [
          '#type' => 'table',
          '#rows' => $data,
        ],
      ],
      'details__api_products' => [
        '#type' => 'details',
        '#title' => t('API Products'),
        '#open' => TRUE,
        'apiProducts' => [
          '#type' => 'table',
          '#rows' => $data_apiProducts,
          '#attributes' => [
            'class' => [
              'table__view__apps_dashboard__api_products',
            ],
          ],
        ],
      ],
      'edit__action' => [
        '#type' => 'link',
        '#title' => t('Edit'),
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
        '#title' => t('Back'),
        '#attributes' => [
          'class' => [
            'button',
          ],
        ],
        '#url' => $return_url,
      ],
    ];

    return $display;
  }

}
