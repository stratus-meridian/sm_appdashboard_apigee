<?php

namespace Drupal\sm_appdashboard_apigee_rules\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'ApigeeAppStatusChangeAction' action.
 *
 * @RulesAction(
 *  id = "apigee_app_status_change_action",
 *  label = @Translation("Apigee app status change action"),
 *  category = @Translation("apigee_app_status_change_action"),
 *  context = {
 *     "Apigee apps status changed" = @ContextDefinition("entity",
 *       label = @Translation("After apps status gets changed."),
 *       description = @Translation("When the apigee apps status getting changed.")
 *     ),
 *  }
 * )
 */
class ApigeeAppStatusChangeAction extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  public function doExecute($object = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {

  }

}
