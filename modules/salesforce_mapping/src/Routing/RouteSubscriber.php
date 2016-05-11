<?php

/**
 * @file
 * Contains \Drupal\salesforce_mapping\Routing\RouteSubscriber.
 */

namespace Drupal\salesforce_mapping\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      foreach (array('view', 'add', 'edit', 'delete') as $op) {
        if ($route = $this->getSalesforceMappedObjectRoute($entity_type, $op)) {
          $routename = "entity.$entity_type_id.salesforce_$op";
          $collection->add($routename, $route);
        }
      }
    }
  }

  /**
   * Gets the devel load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $op
   *   The salesforce mapped object operation. view, edit, add, or delete.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSalesforceMappedObjectRoute(EntityTypeInterface $entity_type, $op) {
    if ($path = $entity_type->getLinkTemplate('salesforce')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($path . "/$op");
      $route
        ->addDefaults([
          '_controller' => "\Drupal\salesforce_mapping\Controller\SalesforceMappedObjectController::$op",
          '_title' => "Salesforce mapped object $op",
        ])
        ->addRequirements([
          '_permission' => 'administer salesforce',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_salesforce_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', 100);
    return $events;
  }

}