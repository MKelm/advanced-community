<?php
/**
 * Advanced community notification settings page
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
* Basic class page module
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_content.php');

/**
 * Advanced community notification settings page
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunityNotificationSettingsPage extends base_content implements PapayaPluginCacheable {

  /**
   * Use a advanced community parameter group name
   * @var string
   */
  public $paramName = 'acns';

  /**
  * Content edit fields
  * @var array $editFields
  */
  public $editFields = array(
    'page_title' => array(
      'Title', 'isNoHTML', TRUE, 'input', 200, '', 'Notification settings'
    ),
    'Captions',
    'caption_dialog_checkbox_by_message' => array(
      'Dialog Checkbox By Message', 'isNoHTML', TRUE, 'input', 200, '', 'By message'
    ),
    'caption_dialog_checkbox_by_email' => array(
      'Dialog Checkbox By E-Mail', 'isNoHTML', TRUE, 'input', 200, '', 'By E-Mail'
    ),
    'caption_dialog_button' => array(
      'Dialog Button', 'isNoHTML', TRUE, 'input', 200, '', 'Save'
    ),
    'Message',
    'message_dialog_input_error' => array(
      'Dialog Input Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Invalid input. Please check the field(s) "%s".'
    ),
    'message_no_login' => array(
      'No Login', 'isNoHTML', TRUE, 'input', 200, '', 'Please login to get notification settings.'
    )
  );

  /**
   * Settings object
   * @var ACommunityNotificationSettings
   */
  protected $_settings = NULL;

  /**
   * Cache definition
   * @var PapayaCacheIdentifierDefinition
   */
  protected $_cacheDefiniton = NULL;

  /**
   * Current ressource
   * @var ACommunityUiContentRessource
   */
  protected $_ressource = NULL;

  /**
   * Define the cache definition for output.
   *
   * @see PapayaPluginCacheable::cacheable()
   * @param PapayaCacheIdentifierDefinition $definition
   * @return PapayaCacheIdentifierDefinition
   */
  public function cacheable(PapayaCacheIdentifierDefinition $definition = NULL) {
    if (isset($definition)) {
      $this->_cacheDefiniton = $definition;
    } elseif (NULL == $this->_cacheDefiniton) {
      $this->_cacheDefiniton = new PapayaCacheIdentifierDefinitionBoolean(FALSE);
    }
    return $this->_cacheDefiniton;
  }

  /**
   * Check url name to fix wrong page names
   *
   * @param string $currentFileName
   * @param string $outputMode
   */
  public function checkURLFileName($currentFileName, $outputMode) {
    $this->setRessourceData();
    $this->settings()->checkURLFileName(
      $this, $currentFileName, $outputMode, 's-notification-settings'
    );
  }

  /**
   * Set surfer ressource data to load corresponding surfer
   */
  public function setRessourceData() {
    if (is_null($this->_ressource)) {
      $this->_ressource = $this->settings()->ressource();
      $this->_ressource->set(
        'surfer', NULL, array('surfer' => array()), NULL, NULL, 'is_selected'
      );
    }
    return $this->_ressource;
  }

  /**
  * Get (and, if necessary, initialize) the ACommunityNotificationSettings object
  *
  * @return ACommunityNotificationSettings $settings
  */
  public function settings(ACommunityNotificationSettings $settings = NULL) {
    if (isset($settings)) {
      $this->_settings = $settings;
    } elseif (is_null($this->_settings)) {
      include_once(dirname(__FILE__).'/../Settings.php');
      $this->_settings = new ACommunityNotificationSettings();
      $this->_settings->parameterGroup($this->paramName);
      $this->_settings->data()->languageId = $this->papaya()->request->languageId;
      $this->_settings->module = $this;
    }
    return $this->_settings;
  }

  /**
  * Get parsed data
  *
  * @return string $result
  */
  function getParsedData() {
    $this->initializeParams();
    $this->setRessourceData();
    $this->setDefaultData();
    $captionNames = array(
      'caption_dialog_checkbox_by_message', 'caption_dialog_checkbox_by_email', 'caption_dialog_button'
    );
    $messageNames = array(
      'message_dialog_input_error', 'message_no_login'
    );
    $this->settings()->data()->setPluginData($this->data, $captionNames, $messageNames);
    return $this->settings()->getXml();
  }
}