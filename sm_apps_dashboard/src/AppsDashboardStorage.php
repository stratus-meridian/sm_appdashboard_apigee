<?php

namespace Drupal\sm_apps_dashboard;

use Drupal\apigee_edge\Entity\ApiProductInterface;

class AppsDashboardStorage {
	/**
	 * Load Labels
	 */
	public static function labels() {
		$labels = array(
			'labelDisplayName' => t('App Display Name'),
			'labelEmail' => t('Developer Email'),
			'labelCompany' => t('Company'),
			'labelStatus' => t('Overall App Status'),
			'labelOnwerActive' => t('Active user in the site?'),
			'labelDateTimeCreated' => t('App Date/Time Created'),
			'labelDateTimeModified' => t('App Date/Time Modified'),
			'labelOperations' => t('Operations'),
		);

		return $labels;
	}

	/**
	 * Retrieve Apps Details (Developer and Team Apps)
	 */
	public static function getAllAppDetails() {
		$apps = array();

		$entity = \Drupal::entityTypeManager();

		$devApps_storage = $entity->getStorage('developer_app');
		$devApps = $devApps_storage->loadMultiple();

		$teamApps_storage = $entity->getStorage('team_app');
		$teamApps = $teamApps_storage->loadMultiple();

		$apps = array_merge($devApps, $teamApps);

		return $apps;
	}

	/**
	 * Retrieve Apps Details (by ID)
	 */
	public static function getAppDetailsById($type, $id) {
		$entity = \Drupal::entityTypeManager();

		if (isset($type) && isset($id))
			$app = $entity->getStorage($type)->load($id);

		return $app;
	}

	/**
	 * Retrieve API Products
	 */
	public static function getApiProducts($app) {
		$data_apiProducts = array();

		$appCredentials = $app->getCredentials();

		foreach($appCredentials[0]->getApiProducts() as $apiProduct) {
			$data_apiProducts[] = array(
				// array(
				// 	'data' => $apiProduct->getApiProduct(),
				// 	'header' => TRUE,
				// ),
				$apiProduct->getApiProduct(),
				$apiProduct->getStatus(),
			);
		}

		return $data_apiProducts;
	}

	public static function startsWith($string, $startString) {
		$len = strlen($startString);
		return (substr($string, 0, $len) === $startString);
	}
}
