<?php
/**
 * Advanced community group page
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
 * Advanced community group page
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunityGroupPage extends base_content implements PapayaPluginCacheable {

  /**
   * Use a advanced community parameter group name
   * @var string
   */
  public $paramName = 'acg';

  /**
  * Content edit fields
  * @var array $editFields
  */
  public $editFields = array(
    'image_size' => array(
      'Avatar Size', 'isNum', TRUE, 'input', 30, '', 160
    ),
    'image_resize_mode' => array(
      'Avatar Resize Mode', 'isAlpha', TRUE, 'translatedcombo',
       array(
         'abs' => 'Absolute', 'max' => 'Maximum', 'min' => 'Minimum', 'mincrop' => 'Minimum cropped'
       ), '', 'mincrop'
    ),
    'Captions',
    'caption_time' => array(
      'Exists Sine', 'isNoHTML', TRUE, 'input', 200, '', 'Exists since'
    ),
    'Message',
    'message_no_group' => array(
      'No Group', 'isNoHTML', TRUE, 'input', 200, '', 'No group selected.'
    )
  );

  /**
   * Group object
   * @var ACommunityGroup
   */
  protected $_group = NULL;

  /**
   * Cache definition
   * @var PapayaCacheIdentifierDefinition
   */
  protected $_cacheDefiniton = NULL;

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
      $definitionValues = array('acommunity_group_page');
      $ressource = $this->setRessourceData();
      if (!empty($ressource)) {
        $command = NULL;
        if (empty($command)) {
          $currentSurferId = $this->surfer()->data()->currentSurferId();
          include_once(dirname(__FILE__).'/../Cache/Identifier/Values.php');
          $values = new ACommunityCacheIdentifierValues();
          $definitionValues[] = $currentSurferId;
          $definitionValues[] = $ressource['id'];
          $definitionValues[] = $values->lastChangeTime('group_'.$ressource['id']);
        } else {
          $this->_cacheDefiniton = new PapayaCacheIdentifierDefinitionBoolean(FALSE);
        }
      }
      if (is_null($this->_cacheDefiniton)) {
        $this->_cacheDefiniton = new PapayaCacheIdentifierDefinitionGroup(
          new PapayaCacheIdentifierDefinitionValues($definitionValues),
          new PapayaCacheIdentifierDefinitionPage()
        );
      }
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
    return $this->surfer()->checkURLFileName($this, $currentFileName, $outputMode, '-page');
  }

  /**
   * Set group ressource data to load corresponding group
   */
  public function setRessourceData() {
    return $this->group()->data()->ressource('group', $this, array('group' => 'group_id'));
  }

  /**
  * Get (and, if necessary, initialize) the ACommunityGroup object
  *
  * @return ACommunityGroup $group
  */
  public function group(ACommunityGroup $group = NULL) {
    if (isset($group)) {
      $this->_group = $group;
    } elseif (is_null($this->_group)) {
      include_once(dirname(__FILE__).'/../Group.php');
      $this->_group = new ACommunityGroup();
      $this->_group->parameterGroup($this->paramName);
      $this->_group->data()->languageId = $this->papaya()->request->languageId;
    }
    return $this->_group;
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
    $captionNames = array('caption_time');
    $this->group()->data()->setPluginData($this->data, $captionNames, array('message_no_group'));
    return $this->group()->getXml();
  }
}