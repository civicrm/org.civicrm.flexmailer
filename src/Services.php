<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Civi\FlexMailer\FlexMailer as FM;

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

    $container->setDefinition('civi_flexmailer_api_overrides', new Definition('Civi\API\Provider\ProviderInterface'))
      ->setFactory(array(__CLASS__, 'createApiOverrides'));

    $container->setDefinition('civi_flexmailer_abdicator', new Definition('Civi\FlexMailer\Listener\Abdicator'));
    $container->setDefinition('civi_flexmailer_default_batcher', new Definition('Civi\FlexMailer\Listener\DefaultBatcher'));
    $container->setDefinition('civi_flexmailer_default_composer', new Definition('Civi\FlexMailer\Listener\DefaultComposer'));
    $container->setDefinition('civi_flexmailer_open_tracker', new Definition('Civi\FlexMailer\Listener\OpenTracker'));
    $container->setDefinition('civi_flexmailer_basic_headers', new Definition('Civi\FlexMailer\Listener\BasicHeaders'));
    $container->setDefinition('civi_flexmailer_to_header', new Definition('Civi\FlexMailer\Listener\ToHeader'));
    $container->setDefinition('civi_flexmailer_attachments', new Definition('Civi\FlexMailer\Listener\Attachments'));
    $container->setDefinition('civi_flexmailer_bounce_tracker', new Definition('Civi\FlexMailer\Listener\BounceTracker'));
    $container->setDefinition('civi_flexmailer_default_sender', new Definition('Civi\FlexMailer\Listener\DefaultSender'));
    $container->setDefinition('civi_flexmailer_hooks', new Definition('Civi\FlexMailer\Listener\HookAdapter'));

    $container->setDefinition('civi_flexmailer_html_click_tracker', new Definition('Civi\FlexMailer\ClickTracker\HtmlClickTracker'));
    $container->setDefinition('civi_flexmailer_text_click_tracker', new Definition('Civi\FlexMailer\ClickTracker\TextClickTracker'));

    foreach (self::getListenerSpecs() as $listenerSpec) {
      $container->findDefinition('dispatcher')->addMethodCall('addListenerService', $listenerSpec);
    }

    $container->findDefinition('civi_api_kernel')->addMethodCall('registerApiProvider', array(new Reference('civi_flexmailer_api_overrides')));
  }

  public static function registerListeners(ContainerAwareEventDispatcher $dispatcher) {
    foreach (self::getListenerSpecs() as $listenerSpec) {
      $dispatcher->addListenerService($listenerSpec[0], $listenerSpec[1], $listenerSpec[2]);
      $dispatcher->addSubscriberService('civi_flexmailer_api_overrides', 'Civi\FlexMailer\API\Overrides');
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
    $listenerSpecs = array();

    $listenerSpecs[] = array(FM::EVENT_RUN, array('civi_flexmailer_default_composer', 'onRun'), FM::WEIGHT_MAIN);
    $listenerSpecs[] = array(FM::EVENT_RUN, array('civi_flexmailer_abdicator', 'onRun'), FM::WEIGHT_END);

    $listenerSpecs[] = array(FM::EVENT_WALK, array('civi_flexmailer_default_batcher', 'onWalk'), FM::WEIGHT_END);

    $listenerSpecs[] = array(FM::EVENT_COMPOSE, array('civi_flexmailer_basic_headers', 'onCompose'), FM::WEIGHT_PREPARE);
    $listenerSpecs[] = array(FM::EVENT_COMPOSE, array('civi_flexmailer_to_header', 'onCompose'), FM::WEIGHT_PREPARE);
    $listenerSpecs[] = array(FM::EVENT_COMPOSE, array('civi_flexmailer_bounce_tracker', 'onCompose'), FM::WEIGHT_PREPARE);
    $listenerSpecs[] = array(FM::EVENT_COMPOSE, array('civi_flexmailer_default_composer', 'onCompose'), FM::WEIGHT_MAIN - 100);
    $listenerSpecs[] = array(FM::EVENT_COMPOSE, array('civi_flexmailer_attachments', 'onCompose'), FM::WEIGHT_ALTER);
    $listenerSpecs[] = array(FM::EVENT_COMPOSE, array('civi_flexmailer_open_tracker', 'onCompose'), FM::WEIGHT_ALTER);
    $listenerSpecs[] = array(FM::EVENT_COMPOSE, array('civi_flexmailer_hooks', 'onCompose'), FM::WEIGHT_ALTER - 100);

    $listenerSpecs[] = array(FM::EVENT_SEND, array('civi_flexmailer_default_sender', 'onSend'), FM::WEIGHT_END);

    return $listenerSpecs;
  }

  /**
   * Tap into the API kernel and override some of the core APIs.
   *
   * @return \Civi\API\Provider\AdhocProvider
   */
  public static function createApiOverrides() {
    $provider = new \Civi\API\Provider\AdhocProvider(3, 'Mailing');
    // FIXME: stay in sync with upstream perms
    $provider->addAction('preview', 'access CiviMail', '\Civi\FlexMailer\API\MailingPreview::preview');
    return $provider;
  }

}
