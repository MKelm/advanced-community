<?php
/**
 * Advanced community surfer status
 *
 * @copyright 2013 by Martin Kelm
 * @link http://idx.shrt.ws
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 *
 * You can redistribute and/or modify this script under the terms of the GNU General Public
 * License (GPL) version 2, provided that the copyright and license notes, including these
 * lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */

/**
 * Base ui content object
 */
require_once(dirname(__FILE__).'/../Ui/Content.php');

/**
 * Advanced community surfer status
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunitySurferStatus extends ACommunityUiContent {

  /**
   * Get/set surfer status data
   *
   * @param ACommunitySurferStatusData $data
   * @return ACommunitySurferStatusData
   */
  public function data(ACommunitySurferStatusData $data = NULL) {
    if (isset($data)) {
      $this->_data = $data;
    } elseif (is_null($this->_data)) {
      include_once(dirname(__FILE__).'/Status/Data.php');
      $this->_data = new ACommunitySurferStatusData();
      $this->_data->papaya($this->papaya());
      $this->_data->owner = $this;
    }
    return $this->_data;
  }

  /**
  * Create dom node structure of the given object and append it to the given xml
  * element node.
  *
  * @param PapayaXmlElement $parent
  */
  public function appendTo(PapayaXmlElement $parent) {
    $status = $parent->appendElement('acommunity-surfer-status');
    $this->data()->initialize();
    $ressource = $this->ressource();
    if (!isset($ressource->id)) {
      $message = $status->appendElement('message', array('type' => 'no-login'));
      $message->appendXml($this->data()->messages['no_login']);
    } else {
      $activeSurfer = $status->appendElement(
        'active-surfer',
        array(
          'name' => $this->data()->surfer['name'],
          'avatar' => PapayaUtilStringXml::escapeAttribute($this->data()->surfer['avatar'])
        )
      );
      $activeSurfer->appendElement(
        'edit-link', array('caption' => $this->data()->captions['edit_link']),
        PapayaUtilStringXml::escape($this->data()->surfer['edit_link'])
      );
      if (isset($this->data()->surfer['contacts_link'])) {
        $activeSurfer->appendElement(
          'contacts-link', array('caption' => $this->data()->captions['contacts_link']),
          PapayaUtilStringXml::escape($this->data()->surfer['contacts_link'])
        );
      }
      if (isset($this->data()->surfer['contact_requests_link'])) {
        $activeSurfer->appendElement(
          'contact-requests-link', array('caption' => $this->data()->captions['contact_requests_link']),
          PapayaUtilStringXml::escape($this->data()->surfer['contact_requests_link'])
        );
      }
      if (isset($this->data()->surfer['contact_own_requests_link'])) {
        $activeSurfer->appendElement(
          'contact-own-requests-link',
          array('caption' => $this->data()->captions['contact_own_requests_link']),
          PapayaUtilStringXml::escape($this->data()->surfer['contact_own_requests_link'])
        );
      }
      $activeSurfer->appendElement(
        'messages-link', array('caption' => $this->data()->captions['messages_link']),
        PapayaUtilStringXml::escape($this->data()->surfer['messages_link'])
      );
      $activeSurfer->appendElement(
        'groups-link', array('caption' => $this->data()->captions['groups_link']),
        PapayaUtilStringXml::escape($this->data()->surfer['groups_link'])
      );
      $activeSurfer->appendElement(
        'notifications-link', array('caption' => $this->data()->captions['notifications_link']),
        PapayaUtilStringXml::escape($this->data()->surfer['notifications_link'])
      );
      $activeSurfer->appendElement(
        'notification-settings-link',
        array('caption' => $this->data()->captions['notification_settings_link']),
        PapayaUtilStringXml::escape($this->data()->surfer['notification_settings_link'])
      );
      $activeSurfer->appendElement(
        'page-link', array('caption' => ''),
        PapayaUtilStringXml::escape($this->data()->surfer['page_link'])
      );
      $activeSurfer->appendElement(
        'logout-link', array('caption' => $this->data()->captions['logout_link']),
        PapayaUtilStringXml::escape($this->data()->surfer['logout_link'])
      );
    }
  }

}