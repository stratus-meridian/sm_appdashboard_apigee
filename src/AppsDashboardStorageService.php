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
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\TableSort;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides useful tasks and functions.
 */
class AppsDashboardStorageService implements AppsDashboardStorageServiceInterface {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Pager\PagerManagerInterface definition.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * AppsDashboardStorageService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request object.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, RequestStack $request_stack, PagerManagerInterface $pager_manager = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function labels() {
    $labels = [
      ['data' => $this->t('App Display Name'), 'field' => 'fieldDisplayName'],
      ['data' => $this->t('Developer Email'), 'field' => 'fieldEmail'],
      ['data' => $this->t('Company'), 'field' => 'fieldCompany'],
      [
        'data' => $this->t('Overall App Status'),
        'field' => 'fieldStatus',
        'sort' => 'desc',
      ],
      ['data' => $this->t('Active user in the site?'), 'field' => 'fieldOnwerActive'],
      ['data' => $this->t('App Date/Time Created'), 'field' => 'fieldDateTimeCreated'],
      ['data' => $this->t('App Date/Time Modified'), 'field' => 'fieldDateTimeModified'],
      'labelOperations' => $this->t('Operations'),
    ];

    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllAppDetails() {
    $apps = [];

    $devAppsStorage = $this->entityTypeManager->getStorage('developer_app');
    $devApps = $devAppsStorage->loadMultiple();

    if ($this->moduleHandler->moduleExists('apigee_edge_teams')) {
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

    foreach ($apps as $appDetails) {
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

    foreach ($apps as $appDetails) {
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

    $order = TableSort::getOrder($header, $this->requestStack->getCurrentRequest());
    $sort = TableSort::getSort($header, $this->requestStack->getCurrentRequest());
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

    $total = count($items);

    $pagerConstruct = $this->pagerManager->createPager($total, $num_page, $index);
    $current_page = $pagerConstruct->getCurrentPage();

    $chunks = array_chunk($items, $num_page);
    $current_page_items = $chunks[$current_page];

    return $current_page_items;
  }

}
