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

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

/**
 * App Details search form.
 */
class AppDetailsSearchForm extends FormBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appdetails_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $appDisplayName = NULL) {
    $form_state->setAlwaysProcess(FALSE);

    $form = [
      '#method' => 'GET',
      '#token' => FALSE,
      '#attached' => [
        'library' => [
          'sm_appdashboard_apigee/sm-appdashboard-apigee-css',
        ],
      ],
      'search' => [
        '#type' => 'search',
        '#title' => $this->t('Search Keyword'),
        '#default_value' => ($this->requestStack->getCurrentRequest()->get('search') ? $this->requestStack->getCurrentRequest()->get('search') : ''),
      ],
      'search_type' => [
        '#type' => 'select',
        '#title' => $this->t('Search Type'),
        '#options' => [
          'internal_name' => $this->t('Internal Name'),
          'display_name' => $this->t('Display Name'),
          'overall_app_status' => $this->t('Overall App Status'),
        ],
        '#default_value' => ($this->requestStack->getCurrentRequest()->get('search_type') ? $this->requestStack->getCurrentRequest()->get('search_type') : 'internal_name'),
      ],
      'actions' => [
        '#prefix' => '<div class="form-item form-actions js-form-wrapper form-wrapper">',
        '#suffix' => '</div>',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Filter'),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Clear'),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}

