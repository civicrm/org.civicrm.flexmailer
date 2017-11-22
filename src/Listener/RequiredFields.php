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

use CRM_Flexmailer_ExtensionUtil as E;
use Civi\FlexMailer\Event\CheckSendableEvent;

class RequiredFields extends BaseListener {

  private $fields = array(
    'subject',
    'name',
    'from_name',
    'from_email',
    '(body_html|body_text)',
  );

  /**
   * Check for required fields.
   *
   * @param \Civi\FlexMailer\Event\CheckSendableEvent $e
   */
  public function onCheckSendable(CheckSendableEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    foreach ($this->fields as $field) {
      if ($field{0} === '(') {
        $alternatives = explode('|', substr($field, 1, -1));
        $fieldTitle = implode(' or ', array_map(function ($x) {
          return "\"$x\"";
        }, $alternatives));
        $found = $this->hasAny($e->getMailing(), $alternatives);
      }
      else {
        $fieldTitle = "\"$field\"";
        $found = !empty($e->getMailing()->{$field});
      }

      if (!$found) {
        $e->setError($field, E::ts('Field %1 is required.', array(
          1 => $fieldTitle,
        )));
      }
      unset($found);
    }
  }

  /**
   * @param $mailing
   * @param $alternatives
   * @return bool
   */
  protected function hasAny($mailing, $alternatives) {
    foreach ($alternatives as $alternative) {
      if (!empty($mailing->{$alternative})) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
