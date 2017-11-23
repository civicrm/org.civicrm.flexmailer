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

/**
 * Class RequiredTokens
 * @package Civi\FlexMailer\Listener
 *
 * The RequiredTokens listener checks draft mailings for traditional
 * CiviMail tokens like `{action.unsubscribeUrl}`, which are often required
 * to comply with anti-spam regulations.
 */
class RequiredTokens extends BaseListener {

  private $requiredTokens;

  /**
   * @var array
   *
   * List of template-types for which we are capable of enforcing token
   * requirements.
   */
  private $templateTypes;

  /**
   * RequiredTokens constructor.
   */
  public function __construct() {
    $this->templateTypes = array('traditional');
    $this->requiredTokens = array(
      'domain.address' => ts("Domain address - displays your organization's postal address."),
      'action.optOutUrl or action.unsubscribeUrl' => array(
        'action.optOut' => ts("'Opt out via email' - displays an email address for recipients to opt out of receiving emails from your organization."),
        'action.optOutUrl' => ts("'Opt out via web page' - creates a link for recipients to click if they want to opt out of receiving emails from your organization. Alternatively, you can include the 'Opt out via email' token."),
        'action.unsubscribe' => ts("'Unsubscribe via email' - displays an email address for recipients to unsubscribe from the specific mailing list used to send this message."),
        'action.unsubscribeUrl' => ts("'Unsubscribe via web page' - creates a link for recipients to unsubscribe from the specific mailing list used to send this message. Alternatively, you can include the 'Unsubscribe via email' token or one of the Opt-out tokens."),
      ),
    );
  }

  /**
   * Check for required fields.
   *
   * @param \Civi\FlexMailer\Event\CheckSendableEvent $e
   */
  public function onCheckSendable(CheckSendableEvent $e) {
    if (!$this->isActive()) {
      return;
    }
    if (\Civi::settings()->get('disable_mandatory_tokens_check')) {
      return;
    }
    if (!in_array($e->getMailing()->template_type, $this->templateTypes)) {
      return;
    }

    foreach (array('body_html', 'body_text') as $field) {
      $str = $e->getFullBody($field);
      if (empty($str)) {
        continue;
      }
      foreach ($this->findMissingTokens($str) as $token => $desc) {
        $e->setError("{$field}:{$token}", E::ts('This message is missing a required token - {%1}: %2',
          array(1 => $token, 2 => $desc)
        ));
      }
    }
  }

  public function findMissingTokens($str) {
    $missing = array();
    foreach ($this->getRequiredTokens() as $token => $value) {
      if (!is_array($value)) {
        if (!preg_match('/(^|[^\{])' . preg_quote('{' . $token . '}') . '/', $str)) {
          $missing[$token] = $value;
        }
      }
      else {
        $present = FALSE;
        $desc = NULL;
        foreach ($value as $t => $d) {
          $desc = $d;
          if (preg_match('/(^|[^\{])' . preg_quote('{' . $t . '}') . '/', $str)) {
            $present = TRUE;
          }
        }
        if (!$present) {
          $missing[$token] = $desc;
        }
      }
    }
    return $missing;
  }

  /**
   * @return array
   */
  public function getRequiredTokens() {
    return $this->requiredTokens;
  }

  /**
   * @param array $requiredTokens
   */
  public function setRequiredTokens($requiredTokens) {
    $this->requiredTokens = $requiredTokens;
  }

  /**
   * @return array
   *   Ex: array('traditional').
   */
  public function getTemplateTypes() {
    return $this->templateTypes;
  }

  /**
   * Set the list of template-types for which we check tokens.
   *
   * @param array $templateTypes
   *   Ex: array('traditional').
   * @return RequiredTokens
   */
  public function setTemplateTypes($templateTypes) {
    $this->templateTypes = $templateTypes;
    return $this;
  }

  /**
   * Add to the list of template-types for which we check tokens.
   *
   * @param array $templateTypes
   *   Ex: array('traditional').
   * @return RequiredTokens
   */
  public function addTemplateTypes($templateTypes) {
    $this->templateTypes = array_unique(array_merge($this->templateTypes, $templateTypes));
    return $this;
  }

}
