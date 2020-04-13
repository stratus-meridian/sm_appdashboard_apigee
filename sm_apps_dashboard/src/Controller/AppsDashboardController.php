<?php

namespace Drupal\sm_apps_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\sm_apps_dashboard\AppsDashboardStorage;
use Drupal\Core\Url;

/**
 * Defines AppsDashboardController class.
 */
class AppsDashboardController extends ControllerBase {
	public function listApps() {
		/**
		 * Define Table Headers
		 */
		$labelAppDetails = AppsDashboardStorage::labels();

		/**
		 * Retrieve Apps Details (Developer and Team Apps)
		 */
		$apps = AppsDashboardStorage::getAllAppDetails();

		/**
		 * Pass App Details into variables
		 */
		$appDetails = array();

		foreach ($apps as $appKey => $app) {
			if ($app->getEntityTypeId() == 'developer_app') {
				/** Set Developer Apps owner active data **/
				$ownerEntity = $app->getOwner();

				if ($ownerEntity) {
					$appOwnerActive = ($ownerEntity->get('status')->getValue()[0]['value'] == 1 ? $this->t('yes') : $this->t('no'));
				} else {
					$appOwnerActive = $this->t('no');
				}

				/** Set Developer Apps email address data **/
				if ($app->getOwnerId()) {
					if ($ownerEntity) {
						$appDeveloperEmail = ($ownerEntity->getEmail() ? $ownerEntity->getEmail() : '');
					}
				} else {
					$appDeveloperEmail = $app->getCreatedBy();
				}

				$appCompany = '';
			} else {
				$appDeveloperEmail = '';

				/** Set Team Apps company name **/
				$appCompany = $app->getCompanyName();
			}

			$appCredentials = $app->getCredentials();

			/** Setup actions (dropdown) **/
			$view_url = Url::fromRoute('apps_dashboard.view', array(
				'apptype' => $app->getEntityTypeId(),
				'appid' => $appKey,
			));

			$edit_url = Url::fromRoute('apps_dashboard.edit', array(
				'apptype' => $app->getEntityTypeId(),
				'appid' => $appKey,
			));

			$drop_button = array(
				'#type' => 'dropbutton',
				'#links' => array(
					'#view' => array(
						'title' => $this->t('View'),
						'url' => $view_url,
					),
					'#edit' => array(
						'title' => $this->t('Edit'),
						'url' => $edit_url,
					),
				),
			);

			/** App Details array push to variables **/
			array_push($appDetails, array(
				'AppDisplayName' => $app->getDisplayName() . ' [Internal Name: ' . $app->getName() . ']',
				'AppDeveloperEmail' => $appDeveloperEmail,
				'AppCompany' => $appCompany,
				'AppStatus' => ($appCredentials[0]->getStatus() ? $appCredentials[0]->getStatus() : $app->getStatus()),
				'OwnerActive' => $appOwnerActive,
				'AppCreatedAt' => $app->getCreatedAt()->format('l, M. d, Y H:i'),
				'AppModifiedAt' => $app->getlastModifiedAt()->format('l, M. d, Y H:i'),
				'actions' => array(
					'data' => $drop_button,
				),
			));
		}

		/**
		 * Merge into one array variable
		 */
		$arrApps = array(
			'labelAppDetails' => $labelAppDetails,
			'appDetails' => $appDetails,
		);

		$form['table__apps_dashboard'] = [
			'#type' => 'table',
			'#header' => $arrApps['labelAppDetails'],
			'#rows' => $arrApps['appDetails'],
			'#empty' => t('No data found'),
		];

		return $form;
	}

	public function viewApp($apptype, $appid) {
		if (!isset($apptype) || !isset($appid)) {
			drupal_set_message(t('There are errors encountered upon viewing the App Details.'), 'error');
			return new RedirectResponse(Drupal::url('apps_dashboard.list'));
		}

		$appDetails = array();

		/**
		 * Load App Deails
		 */
		$app = AppsDashboardStorage::getAppDetailsById($apptype, $appid);

		if ($app->getEntityTypeId() == 'developer_app') {
			/** Set Developer Apps owner active data **/
			$ownerEntity = $app->getOwner();

			if ($ownerEntity) {
				$appOwnerActive = ($ownerEntity->get('status')->getValue()[0]['value'] == 1 ? $this->t('yes') : $this->t('no'));
			} else {
				$appOwnerActive = $this->t('no');
			}

			/** Set Developer Apps email address data **/
			if ($app->getOwnerId()) {
				if ($ownerEntity) {
					$appDeveloperEmail = ($ownerEntity->getEmail() ? $ownerEntity->getEmail() : '');
				}
			} else {
				$appDeveloperEmail = $app->getCreatedBy();
			}

			$appCompany = '';
		} else {
			$appDeveloperEmail = '';

			/** Set Team Apps company name **/
			$appCompany = $app->getCompanyName();
		}

		/**
		 * Get App Credentials and API Products
		 */
		$appCredentials = $app->getCredentials();
		$apiProducts = AppsDashboardStorage::getApiProducts($app);

		$data_apiProducts = array();

		foreach($apiProducts as $apiProduct) {
			$data_apiProducts[] = array(
				array(
					'data' => $apiProduct[0],
					'header' => TRUE,
				),
				$apiProduct[1],
			);
		}

		/**
		 * Plotting App Details into Table
		 */
		$data = array(
			array(
				array('data' => 'App Type', 'header' => TRUE),
				$apptype,
			),
			array(
				array('data' => 'App Display Name', 'header' => TRUE),
				$app->getDisplayName(),
			),
			array(
				array('data' => 'Internal Name', 'header' => TRUE),
				$app->getName(),
			),
			array(
				array('data' => 'Developer Email Address', 'header' => TRUE),
				$appDeveloperEmail,
			),
			array(
				array('data' => 'Company', 'header' => TRUE),
				$appCompany,
			),
			array(
				array('data' => 'Overall App Status', 'header' => TRUE),
				$appCredentials[0]->getStatus() ? $appCredentials[0]->getStatus() : $app->getStatus(),
			),
			array(
				array('data' => 'Active User in the site?', 'header' => TRUE),
				$appOwnerActive,
			),
			array(
				array('data' => 'App Date/Time Created', 'header' => TRUE),
				$app->getCreatedAt()->format('l, M. d, Y H:i'),
			),
			array(
				array('data' => 'App Date/Time Modified', 'header' => TRUE),
				$app->getLastModifiedAt()->format('l, M. d, Y H:i'),
			),
			array(
				array('data' => 'Modified by', 'header' => TRUE),
				$app->getLastModifiedBy(),
			),
		);

		$return_url = Url::fromRoute('apps_dashboard.list');
		$edit_url = Url::fromRoute('apps_dashboard.edit', array(
			'apptype' => $app->getEntityTypeId(),
			'appid' => $appid,
		));

		$display = array(
			'details__app_details' => array(
				'#type' => 'details',
				'#title' => t('App Details'),
				'#open' => TRUE,
				'table__app_details' => array(
					'#type' => 'table',
					'#rows' => $data,
				),
			),
			'details__api_products' => array(
				'#type' => 'details',
				'#title' => t('API Products'),
				'#open' => TRUE,
				'apiProducts' => array(
					'#type' => 'table',
					'#rows' => $data_apiProducts,
					'#attributes' => array(
						'class' => array(
							'table__view__apps_dashboard__api_products',
						),
					),
				),
			),
			'edit__action' => array(
				'#type' => 'link',
				'#title' => t('Edit'),
				'#attributes' => array(
					'class' => array(
						'button',
						'button--primary'
					),
				),
				'#url' => $edit_url,
			),
			'list__action' => array(
				'#type' => 'link',
				'#title' => t('Back'),
				'#attributes' => array(
					'class' => array(
						'button',
					),
				),
				'#url' => $return_url,
			),
		);

		return $display;
	}
}
