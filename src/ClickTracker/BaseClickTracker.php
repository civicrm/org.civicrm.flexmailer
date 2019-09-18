<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5.10                                                |
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
namespace Civi\FlexMailer\ClickTracker;

class BaseClickTracker {

  public static $getTrackerURL = ['CRM_Mailing_BAO_TrackableURL', 'getTrackerURL'];

  /**
   * Create a trackable URL for a URL with tokens.
   *
   * @param string $url
   * @param int $mailing_id
   * @param int|string $queue_id
   *
   * @return string
   */
  public static function getTrackerURLForUrlWithTokens($url, $mailing_id, $queue_id) {

    // Parse the URL.
    // (not using parse_url because it's messy to reassemble)
    if (!preg_match('/^([^?#]+)([?][^#]*)?(#.*)?$/', $url, $parsed)) {
      // Failed to parse it, give up and don't track it.
      return $url;
    }

    // If we have a token in the URL + path section, we can't tokenise.
    if (strpos($parsed[1], '{') !== FALSE) {
      return $url;
    }

    $trackable_url = $parsed[1];

    // Proces the query parameters, if there are any.
    $tokenised_params = [];
    $static_params = [];
    if (!empty($parsed[2])) {
      $query_key_value_pairs = explode('&', substr($parsed[2], 1));

      // Separate the tokenised from the static parts.
      foreach ($query_key_value_pairs as $_) {
        if (strpos($_, '{') === FALSE) {
          $static_params[] = $_;
        }
        else {
          $tokenised_params[] = $_;
        }
      }
      // Add the static params to the trackable part.
      if ($static_params) {
        $trackable_url .= '?' . implode('&', $static_params);
      }
    }

    // Get trackable URL.
    $getTrackerURL = static::$getTrackerURL;
    $data = $getTrackerURL($trackable_url, $mailing_id, $queue_id);

    // Append the tokenised bits and the fragment.
    if ($tokenised_params) {
      // We know the URL will already have the '?'
      $data .= '&' . implode('&', $tokenised_params);
    }
    if (!empty($parsed[3])) {
      $data .= $parsed[3];
    }
    return $data;
  }
}

