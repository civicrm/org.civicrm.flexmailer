<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
namespace Civi\FlexMailer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class Services
 * @package Civi\FlexMailer
 *
 * Manage the setup of any services used by FlexMailer.
 */
class Services {

  public static function registerServices(ContainerBuilder $container) {
    if (version_compare(\CRM_Utils_System::version(), '4.7.0', '>=')) {
      $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
    }
    $container->setDefinition('civi_flexmailer_abdicator', new Definition('Civi\FlexMailer\Listener\Abdicator'));
    $container->setDefinition('civi_flexmailer_default_batcher', new Definition('Civi\FlexMailer\Listener\DefaultBatcher'));
    $container->setDefinition('civi_flexmailer_default_composer', new Definition('Civi\FlexMailer\Listener\DefaultComposer'));
    $container->setDefinition('civi_flexmailer_open_tracker', new Definition('Civi\FlexMailer\Listener\OpenTracker'));
    $container->setDefinition('civi_flexmailer_basic_headers', new Definition('Civi\FlexMailer\Listener\BasicHeaders'));
    $container->setDefinition('civi_flexmailer_bounce_tracker', new Definition('Civi\FlexMailer\Listener\BounceTracker'));
    $container->setDefinition('civi_flexmailer_default_sender', new Definition('Civi\FlexMailer\Listener\DefaultSender'));
    $container->setDefinition('civi_flexmailer_hooks', new Definition('Civi\FlexMailer\Listener\HookAdapter'));

    foreach (self::getListenerSpecs() as $listenerSpec) {
      $container->findDefinition('dispatcher')->addMethodCall('addListenerService', $listenerSpec);
    }
  }

  public static function registerListeners(ContainerAwareEventDispatcher $dispatcher) {
    foreach (self::getListenerSpecs() as $listenerSpec) {
      $dispatcher->addListenerService($listenerSpec[0], $listenerSpec[1], $listenerSpec[2]);
    }
  }

  /**
   * Get a list of listeners required for FlexMailer.
   *
   * This is a standalone, private function because we're experimenting
   * with how exactly to handle the registration -- e.g. via
   * `registerServices()` or via `registerListeners()`.
   *
   * @return array
   *   Arguments to pass to addListenerService($eventName, $callbackSvc, $priority).
   */
  protected static function getListenerSpecs() {
    $end = -100;

    $listenerSpecs = array();
    $listenerSpecs[] = array(FlexMailer::EVENT_RUN, array('civi_flexmailer_abdicator', 'onRun'), $end);
    $listenerSpecs[] = array(FlexMailer::EVENT_WALK, array('civi_flexmailer_default_batcher', 'onWalkBatches'), $end);
    $listenerSpecs[] = array(FlexMailer::EVENT_RUN, array('civi_flexmailer_default_composer', 'onRun'), 0);
    $listenerSpecs[] = array(FlexMailer::EVENT_COMPOSE, array('civi_flexmailer_default_composer', 'onComposeBatch'), $end);
    $listenerSpecs[] = array(FlexMailer::EVENT_ALTER, array('civi_flexmailer_basic_headers', 'onAlterBatch'), -20);
    $listenerSpecs[] = array(FlexMailer::EVENT_ALTER, array('civi_flexmailer_bounce_tracker', 'onAlterBatch'), -20);
    $listenerSpecs[] = array(FlexMailer::EVENT_ALTER, array('civi_flexmailer_open_tracker', 'onAlterBatch'), -20);
    $listenerSpecs[] = array(FlexMailer::EVENT_ALTER, array('civi_flexmailer_hooks', 'onAlterBatch'), -30);
    $listenerSpecs[] = array(FlexMailer::EVENT_SEND, array('civi_flexmailer_default_sender', 'onSendBatch'), $end);
    return $listenerSpecs;
  }

}
