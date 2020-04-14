<?php

namespace Drupal\sm_appdashboard_apigee\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sm_appdashboard_apigee\AppsDashboardStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_edge\SDKConnectorInterface;
use Apigee\Edge\Api\Management\Controller\DeveloperAppController;
use Apigee\Edge\Api\Management\Controller\DeveloperAppCredentialController;
use Apigee\Edge\Api\Management\Entity\DeveloperApp;
use Apigee\Edge\Api\Management\Controller\CompanyAppController;
use Apigee\Edge\Api\Management\Controller\CompanyAppCredentialController;
use Apigee\Edge\Api\Management\Controller\CompanyApp;
use Apigee\Edge\Exception\ApiException;
use Apigee\Edge\Exception\ApiRequestException;
use Apigee\Edge\Exception\ClientErrorException;
use Apigee\Edge\Exception\ServerErrorException;
use Apigee\Edge\Structure\AttributesProperty;

/**
 * Defines AppDetailsEditForm class.
 *
 * Author: Mer Alvin A. Grita (mer.grita@stratusmeridian.com)
 *
 */
class AppDetailsEditForm extends FormBase {
	/**
	 * The SDK connector service.
	 *
	 * @var \Drupal\apigee_edge\SDKConnectorInterface
	 */
	private $connector;

	/**
	 * Constructs a AppDetailsEditForm.
	 *
	 * @param \GuzzleHttp\ClientInterface $http_client
	 *   The HTTP client.
	 * @param \Drupal\apigee_edge\SDKConnectorInterface $connector
	 *   The SDK connector service.
	 */
	public function __construct(SDKConnectorInterface $connector) {
		$this->connector = $connector;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('apigee_edge.sdk_connector')
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'appdetails__edit';
	}

	public function buildForm(array $form, FormStateInterface $form_state, $apptype = NULL, $appid = NULL) {
		try {
			$this->connector->testConnection();
		} catch (\Exception $exception) {
			$this->messenger()->addError($this->t('Cannot connect to Apigee Edge server. Please ensure that <a href=":link">Apigee Edge connection settings</a> are correct.', [
				':link' => Url::fromRoute('apigee_edge.settings')->toString(),
			]));
			return $form;
		}

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
			/**
			 * Set Developer Apps owner active data
			 */
			$ownerEntity = $app->getOwner();

			if ($ownerEntity) {
				$appOwnerActive = ($ownerEntity->get('status')->getValue()[0]['value'] == 1 ? $this->t('yes') : $this->t('no'));
			} else {
				$appOwnerActive = $this->t('no');
			}

			/**
			 * Set Developer Apps email address data
			 */
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

			/**
			 * Set Team Apps company name
			 */
			$appCompany = $app->getCompanyName();
		}

		/**
		 * Get App Credentials and API Products
		 */
		$appCredentials = $app->getCredentials();
		$apiProducts = AppsDashboardStorage::getApiProducts($app);

		/**
		 * Get App Overall Status
		 */
		$appOverallStatus = AppsDashboardStorage::getOverallStatus($app);

		$data_apiProducts = array();

		/**
		 * Get API Products
		 */
		$i = 0;
		foreach($apiProducts as $apiProduct) {
			$data_apiProducts['selectbox_products_'.$i] = array(
				'#type' => 'select',
				'#title' => $apiProduct[0],
				'#description' => $this->t('Set action to <strong>approved</strong> or <strong>revoked</strong>.'),
				'#options' => array(
					'approved' => $this->t('approved'),
					'revoked'=> $this->t('revoked'),
				),
				'#default_value' => $apiProduct[1],
			);
			$i++;
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
				$appOverallStatus,
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

		$form = array(
			'details__app_details' => array(
				'#type' => 'details',
				'#title' => $this->t('App Details'),
				'#open' => TRUE,
				'table__app_details' => array(
					'#type' => 'table',
					'#rows' => $data,
				),
			),
			'details__api_products' => array(
				'#type' => 'details',
				'#title' => $this->t('API Products'),
				'#open' => TRUE,
				'api_products' => $data_apiProducts,
				'app_consumer_key' => array(
					'#type' => 'hidden',
					'#value' => $appCredentials[0]->getConsumerKey(),
				),
				'app_developer_email' => array(
					'#type' => 'hidden',
					'#value' => $appDeveloperEmail,
				),
				'app_company' => array(
					'#type' => 'hidden',
					'#value' => $appCompany,
				),
				'app_internal_name' => array(
					'#type' => 'hidden',
					'#value' => rawurlencode($app->getName()),
				),
				'app_entity_type' => array(
					'#type' => 'hidden',
					'#value' => $apptype,
				),
			),
			'actions' => array(
				'#type' => 'actions',
				'submit' => array(
					'#type' => 'submit',
					'#value' => $this->t('Save'),
					'#attributes' => array(
						'class' => array(
							'button',
							'button--primary'
						),
					),
				),
				'cancel' => array(
					'#type' => 'link',
					'#title' => 'Cancel',
					'#attributes' => array(
						'class' => array(
							'button'
						),
					),
					'#url' => Url::fromRoute('apps_dashboard.list'),
				),
			),
		);

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {

	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		/**
		 * Get API Products (Name and selected Status)
		 */
		$FormSelectBoxApiProducts = $form['details__api_products']['api_products'];

		$val_apiproducts = array();

		/**
		 * Array push API Products to $val_apiproducts
		 */
		foreach($FormSelectBoxApiProducts as $selectboxKey => $selectboxValue) {
			if (AppsDashboardStorage::startsWith($selectboxKey, 'selectbox_products') == TRUE) {
				array_push($val_apiproducts, array(
					'apiproducts_name' => $selectboxValue['#title'],
					'apiproducts_status' => $form_state->getValue($selectboxKey),
				));
			}
		}

		if ($form_state->getValue('app_entity_type') == 'developer_app') {
			/**
			 * Open New Developer App Controller
			 */
			$devAppController = new DeveloperAppController($this->connector->getOrganization(), $form_state->getValue('app_developer_email'), $this->connector->getClient());

			/**
			 * Create a try and catch to test the connection of Developer App Controller
			 */
			try {
				/**
				 * Open Developer App Credentials' Controller
				 */
				$devAppCredentialsController = new DeveloperAppCredentialController($this->connector->getOrganization(), $form_state->getValue('app_developer_email'), $form_state->getValue('app_internal_name'), $this->connector->getClient());

				/**
				 * Set and save the new status of API Products associated with the this Developer App
				 */
				foreach($val_apiproducts as $val_apiproduct) {
					$apiProductStatus = ($val_apiproduct['apiproducts_status'] == 'approved' ? DeveloperAppCredentialController::STATUS_APPROVE : DeveloperAppCredentialController::STATUS_REVOKE);
					$devAppCredentialsController->setApiProductStatus($form_state->getValue('app_consumer_key'), $val_apiproduct['apiproducts_name'], $apiProductStatus);
				}

				/**
				 * Close all open controllers
				 */
				$devAppCredentialsController = NULL;
				$devAppController = NULL;

				drupal_set_message(t('App Details are successfully updated.'), 'status');
				$form_state->setRedirect('apps_dashboard.list');
			} catch (ClientErrorException $err) {
				if ($err->getEdgeErrorCode()) {
					drupal_set_message(t('There is an error encountered. Error Code: ') . $err->getEdgeErrorCode(), 'error');
				} else {
					drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
				}
			} catch (ServerErrorException $err) {
				drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
			} catch (ApiRequestException $err) {
				drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
			} catch (ApiException $err) {
				drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
			}
		} else {
			/**
			 * Open New Company App Controller
			 */
			$compAppController = new CompanyAppController($this->connector->getOrganization(), $form_state->getValue('app_company'), $this->connector->getClient());

			try {
				/**
				 * Open Company App Credentials' Controller
				 */
				$compAppCredentialsController = new CompanyAppCredentialController(
					$this->connector->getOrganization(),
					$form_state->getValue('app_company'),
					$form_state->getValue('app_internal_name'),
					$this->connector->getClient()
				);

				/**
				 * Set and save the new status of API Products associated with the this Company App
				 */
				foreach($val_apiproducts as $val_apiproduct) {
					$apiProductStatus = ($val_apiproduct['apiproducts_status'] == 'approved' ? CompanyAppCredentialController::STATUS_APPROVE : CompanyAppCredentialController::STATUS_REVOKE);
					$compAppCredentialsController->setApiProductStatus($form_state->getValue('app_consumer_key'), $val_apiproduct['apiproducts_name'], $apiProductStatus);
				}

				/**
				 * Close all open controllers
				 */
				$compAppCredentialsController = NULL;
				$compAppController = NULL;

				drupal_set_message(t('App Details are successfully updated.'), 'status');
				$form_state->setRedirect('apps_dashboard.list');
			} catch (ClientErrorException $err) {
				if ($err->getEdgeErrorCode()) {
					drupal_set_message(t('There is an error encountered. Error Code: ') . $err->getEdgeErrorCode(), 'error');
				} else {
					drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
				}
			} catch (ServerErrorException $err) {
				drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
			} catch (ApiRequestException $err) {
				drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
			} catch (ApiException $err) {
				drupal_set_message(t('There is an error encountered. Error Code: ') . $err, 'status');
			}
		}
	}
}
