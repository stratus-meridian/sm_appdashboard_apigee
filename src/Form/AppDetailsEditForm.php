<?php

namespace Drupal\sm_appdashboard_apigee\Form;

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

use Apigee\Edge\Api\Management\Controller\DeveloperAppController;
use Apigee\Edge\Api\Management\Controller\DeveloperAppCredentialController;
use Apigee\Edge\Api\Management\Controller\CompanyAppController;
use Apigee\Edge\Api\Management\Controller\CompanyAppCredentialController;
use Apigee\Edge\Exception\ApiException;
use Apigee\Edge\Exception\ApiRequestException;
use Apigee\Edge\Exception\ClientErrorException;
use Apigee\Edge\Exception\ServerErrorException;
use Drupal\apigee_edge\Entity\App;
use Drupal\apigee_edge\Entity\Controller\AppCredentialControllerInterface;
use Drupal\apigee_edge\Entity\Controller\DeveloperAppCredentialControllerFactoryInterface;
use Drupal\apigee_edge_teams\Entity\Controller\TeamAppCredentialControllerFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use DrupalCore\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to edit the App Details and change API Products status.
 */
class AppDetailsEditForm extends FormBase {

  /**
   * The SDK connector service.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $connector;

  /**
   * AppsDashboardStorageServiceInterface definition.
   *
   * @var Drupal\sm_appdashboard_apigee\AppsDashboardStorageServiceInterface
   */
  protected $appsDashboardStorage;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->connector = $container->get('apigee_edge.sdk_connector');
    $instance->appsDashboardStorage = $container->get('sm_appsdashboard_apigee.appsdashboard_storage');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sm_appdashboard_apigee_appdetails_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $apptype = NULL, $appid = NULL) {
    try {
      $this->connector->testConnection();
    }
    catch (\Exception $exception) {
      $link = Link::fromTextAndUrl($this->t('Apigee Edge connection settings'), Url::fromRoute('apigee_edge.settings'));
      $this->messenger()->addError($this->t('Cannot connect to Apigee Edge server. Please ensure that @link are correct.', ['@link' => $link]));
      return $form;
    }

    if (!isset($apptype) || !isset($appid)) {
      $this->messenger()->addError($this->t('There are errors encountered upon viewing the App Details.'));
      $path = Url::fromRoute('apps_dashboard.list', [])->toString();
      $response = new RedirectResponse($path);
      $response->send();
    }

    // Load App Details.
    /* @var $app App*/
    $app = $this->appsDashboardStorage->getAppDetailsById($apptype, $appid);

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
      $appDeveloperEmail = '';

      // Set Team Apps company name.
      $appCompany = $app->getCompanyName();
    }

    // Get App Credentials and API Products.
    $appCredentials = $app->getCredentials();

    $i = 1;

    $data_apiProducts = [];

    foreach($app->getCredentials() as $credential){
      $data_apiProducts[$credential->id()] = [
        '#type' => 'fieldset',
        '#title' => 'Credential #' . $i++,
        'app_consumer_key' => [
          '#type' => 'value',
          '#value' => $credential->getConsumerKey(),
        ],
      ];
      foreach($credential->getApiProducts() as $apiProduct)
      $data_apiProducts[$credential->id()]['apiproduct'][$apiProduct->getApiProduct()]=[
        '#type' => 'select',
        '#title' => $apiProduct->getApiProduct(),
        '#description' => $this->t('Set action to <strong>approved</strong> or <strong>revoked</strong>.'),
        '#options' => [
          'approved' => $this->t('approved'),
          'revoked' => $this->t('revoked'),
        ],
        '#default_value' => $apiProduct->getStatus(),
      ];
    }

    // Get App Overall Status.
    $appOverallStatus = $this->appsDashboardStorage->getOverallStatus($app);


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

    $form = [
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
        '#tree' => TRUE,
        'app_credentials' => $data_apiProducts,
        'app_developer_email' => [
          '#type' => 'value',
          '#value' => $appDeveloperEmail,
        ],
        'app_company' => [
          '#type' => 'value',
          '#value' => $appCompany,
        ],
        'app_internal_name' => [
          '#type' => 'value',
          '#value' => rawurlencode($app->getName()),
        ],
        'app_entity_type' => [
          '#type' => 'value',
          '#value' => $apptype,
        ],
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Save'),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => 'Cancel',
          '#attributes' => [
            'class' => [
              'button',
            ],
          ],
          '#url' => Url::fromRoute('apps_dashboard.list'),
        ],
      ],
    ];

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

    try {
      $values = $form_state->getValues();
      /* @var  $developerAppCredentialControllerFactory DeveloperAppCredentialControllerFactoryInterface */
      $developerAppCredentialControllerFactory = \Drupal::service('apigee_edge.controller.developer_app_credential_factory');
      /* @var $teamAppCredentialControllerFactory TeamAppCredentialControllerFactoryInterface */
      $teamAppCredentialControllerFactory = \Drupal::service('apigee_edge_teams.controller.team_app_credential_controller_factory');
      /* @var $credentialController AppCredentialControllerInterface */
      $credentialController = null;
      if ($values['details__api_products']['app_entity_type'] == 'developer_app') {
        $credentialController = $developerAppCredentialControllerFactory->developerAppCredentialController($values['details__api_products']['app_developer_email'], $values['details__api_products']['app_internal_name']);
      } else {
        $credentialController = $teamAppCredentialControllerFactory->teamAppCredentialController($values['details__api_products']['app_company'], $values['details__api_products']['app_internal_name']);
      }

      foreach ($values['details__api_products']['app_credentials'] as $key => $credential) {
        foreach ($values['details__api_products']['app_credentials'][$key]['apiproduct'] as $product_name => $product_status) {
          $credentialController->setApiProductStatus($credential['app_consumer_key'], $product_name,
            $product_status == 'approved' ? AppCredentialControllerInterface::STATUS_APPROVE : AppCredentialControllerInterface::STATUS_REVOKE);
        }
      }
      $this->messenger()->addStatus($this->t('App Details are successfully updated.'));
      $form_state->setRedirect('apps_dashboard.list');
    }
    catch (ClientErrorException $err) {
      if ($err->getEdgeErrorCode()) {
        $this->messenger()->addError($this->t('There is an error encountered. Error Code:') . $err->getEdgeErrorCode());
      }
      else {
        $this->messenger()->addStatus($this->t('There is an error encountered. Error Code:') . $err);
      }
    }
    catch (ServerErrorException $err) {
      $this->messenger()->addStatus($this->t('There is an error encountered. Error Code:') . $err);
    }
    catch (ApiRequestException $err) {
      $this->messenger()->addStatus($this->t('There is an error encountered. Error Code:') . $err);
    }
    catch (ApiException $err) {
      $this->messenger()->addStatus($this->t('There is an error encountered. Error Code:') . $err, 'status');
    }
  }
}
