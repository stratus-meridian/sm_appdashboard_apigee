<?php

namespace Drupal\sm_appdashboard_apigee_rules\Plugin\RulesEvent;

use Drupal\apigee_edge_actions\ApigeeActionsEntityTypeHelperInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\apigee_edge\Entity\EdgeEntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;


/**
 * Deriver for Edge entity add_product events.
 */
class AppStatusUpdateEventDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The apigee app entity type manager service.
   *
   * @var \Drupal\apigee_edge_actions\ApigeeActionsEntityTypeHelperInterface
   */
  protected $edgeEntityTypeManager;

  /**
   * AppEventDeriver constructor.
   *
   * @param \Drupal\apigee_edge_actions\ApigeeActionsEntityTypeHelperInterface $edge_entity_type_manager
   *   The apigee app entity type manager service.
   */
  public function __construct(ApigeeActionsEntityTypeHelperInterface $edge_entity_type_manager) {
    $this->edgeEntityTypeManager = $edge_entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('apigee_edge_actions.edge_entity_type_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes(): array {
    return $this->edgeEntityTypeManager->getEntityTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(EdgeEntityTypeInterface $entity_type): array {
    $context = [
      $entity_type->id() => [
        'type' => "entity:{$entity_type->id()}",
        'label' => $entity_type->getLabel(),
      ],
    ];

    // Add additional context for App.
    if ($entity_type->entityClassImplements(AppInterface::class)) {
      // Add the developer to the context.
      $context['developer'] = [
        'type' => 'entity:user',
        'label' => 'Developer',
      ];

      // Add the team to the context.
      if ($entity_type->entityClassImplements(TeamAppInterface::class)) {
        $context['team'] = [
          'type' => 'entity:team',
          'label' => 'Team',
        ];
      }
    }

    return $context;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->getEntityTypes() as $entity_type) {
      $this->derivatives[$entity_type->id()] = [
          'label' => $this->getLabel($entity_type),
          'category' => $entity_type->getLabel(),
          'entity_type_id' => $entity_type->id(),
          'context' => $this->getContext($entity_type),
        ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(EdgeEntityTypeInterface $entity_type): string {
    return $this->t('After the apps status getting changed from Apps Dashboard.');
  }

}