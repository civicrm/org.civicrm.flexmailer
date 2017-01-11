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

/**
 *
 * @copyright CiviCRM LLC (c) 2004-2017
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

// For compat w/v4.6 phpunit
//require_once 'tests/phpunit/.php';
use Civi\FlexMailer\Event\ComposeBatchEvent;
use Civi\FlexMailer\FlexMailerTask;

/**
 * Class SimpleFilterTest
 *
 * @group headless
 */
class SimpleFilterTest extends \CiviUnitTestCase {

  public function setUp() {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(array('org.civicrm.flexmailer'));
    }

    parent::setUp();
  }

  /**
   * Ensure that the utility `SimpleFilter::apply()` correctly filters field.
   */
  public function testApply() {
    $tasks = array();
    $tasks[0] = new FlexMailerTask(1000, 2000, 'asdf', 'foo@example.org');
    $tasks[1] = new FlexMailerTask(1001, 2001, 'fdsa', 'bar@example.org');

    $e = new ComposeBatchEvent(array(), $tasks);

    $tasks[0]->setMailParam('text', 'eat more cheese');
    $tasks[1]->setMailParam('text', 'eat more ice cream');

    SimpleFilter::apply($e, 'text', function ($value) {
      return preg_replace('/more/', 'thoughtfully considered quantities of', $value);
    });

    $this->assertEquals('eat thoughtfully considered quantities of cheese', $tasks[0]->getMailParam('text'));
    $this->assertEquals('eat thoughtfully considered quantities of ice cream', $tasks[1]->getMailParam('text'));
  }

}
