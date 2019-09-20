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
namespace Civi\FlexMailer\Listener;

use Civi\FlexMailer\Event\ComposeBatchEvent;

class OpenTracker extends BaseListener {

  /**
   * Inject open-tracking codes.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive() || !$e->getMailing()->open_tracking) {
      return;
    }

    $config = \CRM_Core_Config::singleton();

    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $mailParams = $task->getMailParams();

      if (!empty($mailParams) && !empty($mailParams['html'])) {
        $tracking_url = $config->userSystem->languageNegotiationURL(
          $config->userFrameworkBaseURL . \Civi::settings()->get('userFrameworkResourceURL') . "/extern/open.php?q=" . $task->getEventQueueId(),
          FALSE,
          TRUE
        );
        $mailParams['html'] .= "\n" . '<img src="' .
          $tracking_url .
          "\" width='1' height='1' alt='' border='0'>";

        $task->setMailParams($mailParams);
      }
    }
  }

}
