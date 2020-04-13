<?php

namespace Drupal\sm_apps_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sm_apps_dashboard\AppsDashboardStorage;
use GuzzleHttp\Client;
use Apigee\Edge\Client as ApigeeClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_edge\SDKConnectorInterface;

class AppDetailsEditForm extends FormBase {
	/**
	 * The HTTP client.
	 *
	 * @var \GuzzleHttp\Client
	 */
	protected $httpClient;

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
	public function __construct(Client $http_client, SDKConnectorInterface $connector) {
		$this->httpClient = $http_client;
		$this->connector = $connector;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('http_client'),
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
				$appOwnerActive = ($ownerEntity->get('status')->getValue()[0]['value'] == 1 ? t('yes') : t('no'));
			} else {
				$appOwnerActive = t('no');
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

		$i = 0;
		foreach($apiProducts as $apiProduct) {
			$data_apiProducts['selectbox_products_'.$i] = array(
				'#type' => 'select',
				'#title' => $apiProduct[0],
				'#description' => t('Set action to <strong>approved</strong> or <strong>revoked</strong>.'),
				'#options' => array(
					'approved' => t('approved'),
					'revoked'=> t('revoked'),
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

		/** Apigee Connection Details **/
		$_getApigeeOrganization = $this->connector->getOrganization();
		$_getApigeeEndPoint = $this->connector->getClient()->getEndPoint();

		$form = array(
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
				'api_products' => $data_apiProducts,
				'api_organization' => array(
					'#type' => 'hidden',
					'#value' => $_getApigeeOrganization,
				),
				'api_endpoint' => array(
					'#type' => 'hidden',
					'#value' => $_getApigeeEndPoint,
				),
				'app_consumer_key' => array(
					'#type' => 'hidden',
					'#value' => $appCredentials[0]->getConsumerKey(),
				),
				'app_developer_email' => array(
					'#type' => 'hidden',
					'#value' => $appDeveloperEmail,
				),
				'app_internal_name' => array(
					'#type' => 'hidden',
					'#value' => rawurlencode($app->getName()),
				),
			),
			'actions' => array(
				'#type' => 'actions',
				'submit' => array(
					'#type' => 'submit',
					'#value' => t('Save'),
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
		$arrFormValues = $form_state->getValues();
		$FormSelectBoxApiProducts = $form['details__api_products']['api_products'];

		$val_apiproducts = array();

		foreach($FormSelectBoxApiProducts as $selectboxKey => $selectboxValue) {
			if (AppsDashboardStorage::startsWith($selectboxKey, 'selectbox_products') == TRUE) {
				array_push($val_apiproducts, array(
					'apiproducts_name' => rawurlencode($selectboxValue['#title']),
					'apiproducts_status' => $form_state->getValue($selectboxKey),
				));
			}
		}

		$val_api_organization = $form_state->getValue('api_organization');
		$val_api_endpoint = $form_state->getValue('api_endpoint');
		$val_api_consumer_key = $form_state->getValue('app_consumer_key');
		$val_app_developer_email = $form_state->getValue('app_developer_email');
		$val_app_internal_name = rawurlencode($form_state->getValue('app_internal_name'));

		foreach($val_apiproducts as $val_apiproduct) {
			$http_request = $val_api_endpoint;
			$http_request .= '/organizations/' . $val_api_organization;
			$http_request .= '/developers/' . $val_app_developer_email;
			$http_request .= '/apps/' . $val_app_internal_name;
			$http_request .= '/keys/' . $val_api_consumer_key;
			$http_request .= '/apiproducts/' . $val_apiproduct['apiproducts_name'];
			$http_request .= ($val_apiproduct['apiproducts_status'] == 'approved' ? '?action=approve' : '?action=revoke');

			//$this->httpClient->post($http_request);
			//$this->connector->getClient()->post($http_request);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {

	}
}
