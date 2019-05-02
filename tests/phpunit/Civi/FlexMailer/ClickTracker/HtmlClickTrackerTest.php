<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

use Civi\FlexMailer\ClickTracker\HtmlClickTracker;

/**
 * Class HtmlClickTrackerTest
 *
 * @group headless
 */
class HtmlClickTrackerTest extends \CiviUnitTestCase {

  public function setUp() {
    if (version_compare(\CRM_Utils_System::version(), '4.7.29', '<')) {
      $this->markTestSkipped('This version of CiviCRM does not support the necessary services.');
    }
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(array('org.civicrm.flexmailer'));
    }

    parent::setUp();
    \Civi::settings()->set('flexmailer_traditional', 'flexmailer');
  }

  public function getHrefExamples() {
    $exs = [];

    // For each example, the test-harness will useHtmlClickTracker to wrap the URL in "tracking(...)".

    $exs[] = [
      // Basic case
      '<p><a href="http://example.com/">Foo</a></p>',
      '<p><a href="tracking(http://example.com/)" rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL
      '<p><a href=\'https://sub.example.com/foo.php?whiz=%2Fbang%2F&pie[fruit]=apple\'>Foo</a></p>',
      '<p><a href=\'tracking(https://sub.example.com/foo.php?whiz=%2Fbang%2F&pie[fruit]=apple)\' rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL, designed to trip-up quote handling
      '<p><a href="javascript:alert(\'Cheese\')">Foo</a></p>',
      '<p><a href="tracking(javascript:alert(\'Cheese\'))" rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL, designed to trip-up quote handling
      '<p><a href=\'javascript:alert("Cheese")\'>Foo</a></p>',
      '<p><a href=\'tracking(javascript:alert("Cheese"))\' rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL, funny whitespace
      '<p><a href="http://example.com/' . "\n" . 'weird">Foo</a></p>',
      '<p><a href="tracking(http://example.com/' . "\n" . 'weird)" rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Many different URLs
      '<p><a href="http://example.com/1">First</a><a href="http://example.com/2">Second</a><a href=\'http://example.com/3\'>Third</a><a href="http://example.com/4">Fourth</a></p>',
      '<p><a href="tracking(http://example.com/1)" rel=\'nofollow\'>First</a><a href="tracking(http://example.com/2)" rel=\'nofollow\'>Second</a><a href=\'tracking(http://example.com/3)\' rel=\'nofollow\'>Third</a><a href="tracking(http://example.com/4)" rel=\'nofollow\'>Fourth</a></p>',
    ];

    return $exs;
  }

  /**
   * @param $inputHtml
   * @param $expectHtml
   * @dataProvider getHrefExamples
   */
  public function testReplaceHref($inputHtml, $expectHtml) {
    $actual = HtmlClickTracker::replaceHrefUrls($inputHtml, function($url) {
      return "tracking($url)";
    });

    $this->assertEquals($expectHtml, $actual, "Check substitutions on text ($inputHtml)");
  }

}
