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
use Drupal\Core\Url;

/**
 * App Details search form.
 */
class AppDetailsSearchForm extends FormBase {

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
      'search' => [
        '#type' => 'search',
        '#title' => $this->t('Search App Details'),
        '#default_value' => $_GET['search'] ?? '',
      ],
      'actions' => [
        '#prefix' => '<div class="form-actions js-form-wrapper form-wrapper">',
        '#suffix' => '</div>',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Filter'),
          '#attributes' => [
            'class' => ['form-actions',
              'button', 'button--primary',
            ],
          ],
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

