<?php

namespace Drupal\sm_appdashboard_apigee;

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

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\TableSort;

/**
 * Provides useful tasks and functions.
 */
class AppsDashboardStorageService implements AppsDashboardStorageServiceInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DefaultService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function labels() {
    $labels = [
      ['data' => t('App Display Name'), 'field' => 'fieldDisplayName'],
      ['data' => t('Developer Email'), 'field' => 'fieldEmail'],
      ['data' => t('Company'), 'field' => 'fieldCompany'],
      [
        'data' => t('Overall App Status'),
        'field' => 'fieldStatus',
        'sort' => 'desc',
      ],
      ['data' => t('Active user in the site?'), 'field' => 'fieldOnwerActive'],
      ['data' => t('App Date/Time Created'), 'field' => 'fieldDateTimeCreated'],
      ['data' => t('App Date/Time Modified'), 'field' => 'fieldDateTimeModified'],
      'labelOperations' => t('Operations'),
    ];

    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllAppDetails() {
    $module_handler = \Drupal::service('module_handler');
    $apps = [];

    $devAppsStorage = $this->entityTypeManager->getStorage('developer_app');
    $devApps = $devAppsStorage->loadMultiple();

    if ($module_handler->moduleExists('apigee_edge_teams')) {
      if ($teamApps_storage = $this->entityTypeManager->getStorage('team_app')) {
        $teamApps = $teamApps_storage->loadMultiple();
      }
      else {
        $teamApps = [];
      }
    }
    else {
      $teamApps = [];
    }

    $apps = array_merge($devApps, $teamApps);

    return $apps;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppDetailsById($type, $id) {

    if (isset($type) && isset($id)) {
      $app = $this->entityTypeManager->getStorage($type)->load($id);
    }

    return $app;
  }

  /**
   * {@inheritdoc}
   */
  public function searchBy($key, $type) {
    $apps = $this->getAllAppDetails();
    $app = [];

    foreach ($apps as $appKey => $appDetails) {
      if ($type == 'internal_name') {
        $getCompareKey = $appDetails->getName();
      }
      elseif ($type == 'display_name') {
        $getCompareKey = $appDetails->getDisplayName();
      }
      elseif ($type == 'overall_app_status') {
        $getCompareKey = $this->getOverallStatus($appDetails);
      }
      elseif ($type == 'company') {
        if ($appDetails->getEntityTypeId() !== 'developer_app') {
          $getCompareKey = $appDetails->getCompanyName();
        }
      }

      if (stripos($getCompareKey, $key) !== FALSE) {
        $app = array_merge($app, [$appDetails->id() => $appDetails]);
      }
    }

    return $app;
  }

  /**
   * {@inheritdoc}
   */
  public function searchByDates($datetime, $type) {
    $apps = $this->getAllAppDetails();

    $datetime_from = strtotime($datetime['from']['date'] . ' ' . $datetime['from']['time']);
    $datetime_to = strtotime($datetime['to']['date'] . ' ' . $datetime['to']['time']);

    $app = [];

    foreach ($apps as $appKey => $appDetails) {
      if ($type == 'date_time_created') {
        $getCompareKey = $appDetails->getCreatedAt()->getTimestamp();
      }
      else {
        $getCompareKey = $appDetails->getLastModifiedAt()->getTimestamp();
      }

      if (($getCompareKey >= $datetime_from) && ($getCompareKey <= $datetime_to)) {
        $app = array_merge($app, [$appDetails->id() => $appDetails]);
      }
    }

    return $app;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiProducts($app) {
    $data_apiProducts = [];

    $appCredentials = $app->getCredentials();

    foreach ($appCredentials[0]->getApiProducts() as $apiProduct) {
      $data_apiProducts[] = [
        $apiProduct->getApiProduct(),
        $apiProduct->getStatus(),
      ];
    }

    return $data_apiProducts;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverallStatus($app) {
    $appCredentials = $app->getCredentials();

    $appStatus = $app->getStatus();
    $appCredStatus = $appCredentials[0]->getStatus();

    static $statuses;

    if (!isset($statuses)) {
      $statuses = [
        'approved' => 0,
        'pending' => 1,
        'revoked' => 2,
      ];
    }

    $appStatus = (array_key_exists($app->getStatus(), $statuses) ? $statuses[$app->getStatus()] : 0);
    $appCredStatus = (array_key_exists($appCredentials[0]->getStatus(), $statuses) ? $statuses[$appCredentials[0]->getStatus()] : 0);
    $appOverallStatus = max($appStatus, $appCredStatus);

    if ($appOverallStatus < 2) {
      foreach ($appCredentials[0]->getApiProducts() as $api_product) {
        if (!array_key_exists($api_product->getStatus(), $statuses)) {
          continue;
        }

        $appOverallStatus = max($appOverallStatus, $statuses[$api_product->getStatus()]);

        if ($appOverallStatus == 2) {
          break;
        }
      }
    }

    $arrStatusSearch = array_search($appOverallStatus, $statuses);

    return $arrStatusSearch;
  }

  /**
   * {@inheritdoc}
   */
  public function startsWith($string, $startString) {
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
  }

  /**
   * {@inheritdoc}
   */
  public function constructSort($rows, $header, $flag = SORT_STRING | SORT_FLAG_CASE) {
    $request = \Drupal::request();

    $order = TableSort::getOrder($header, $request);
    $sort = TableSort::getSort($header, $request);
    $column = $order['sql'];

    foreach ($rows as $row) {
      $temp_array[] = $row[$column];
    }

    if ($sort == 'asc') {
      asort($temp_array, $flag);
    }
    else {
      arsort($temp_array, $flag);
    }

    foreach ($temp_array as $index => $data) {
      $new_rows[] = $rows[$index];
    }

    return $new_rows;
  }

  /**
   * {@inheritdoc}
   */
  public function constructPager($items, $num_page, $index = 0) {
    $pagerManager = \Drupal::service('pager.manager');

    $total = count($items);

    $pagerConstruct = $pagerManager->createPager($total, $num_page, $index);
    $current_page = $pagerConstruct->getCurrentPage();

    $chunks = array_chunk($items, $num_page);
    $current_page_items = $chunks[$current_page];

    return $current_page_items;
  }

}
