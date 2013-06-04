<?php
/**
 * Advanced community surfer page
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
 * Advanced community surfer page
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunityCommentsPage extends base_content implements PapayaPluginCacheable {

  /**
   * Use a advanced community parameter group name
   * @var string
   */
  public $paramName = 'accs';

  /**
  * Content edit fields
  * @var array $editFields
  */
  public $editFields = array(
    'comments_per_page' => array(
      'Comments Per Page', 'isNum', TRUE, 'input', 30, '0 for all comments', 10
    ),
    'comments_per_comment' => array(
      'Comments Per Comment', 'isNum', TRUE, 'input', 30, '0 for all comments', 5
    ),
    'deleted_surfer_handle' => array(
      'Deleted Surfer Handle', 'isAlphaNumChar', TRUE, 'input', 200, '', 'Deleted user'
    ),
    'avatar_size' => array(
      'Avatar Size', 'isNum', TRUE, 'input', 30, '', 40
    ),
    'avatar_resize_mode' => array(
      'Avatar Resize Mode', 'isAlpha', TRUE, 'translatedcombo',
       array(
         'abs' => 'Absolute', 'max' => 'Maximum', 'min' => 'Minimum', 'mincrop' => 'Minimum cropped'
       ), '', 'mincrop'
    ),
    'Captions',
    'caption_dialog_text' => array(
      'Dialog Text', 'isNoHTML', TRUE, 'input', 200, '', 'Text'
    ),
    'caption_dialog_button' => array(
      'Dialog Button', 'isNoHTML', TRUE, 'input', 200, '', 'Add'
    ),
    'caption_command_reply' => array(
      'Reply', 'isNoHTML', TRUE, 'input', 200, '', 'Reply'
    ),
    'caption_command_vote_up' => array(
      'Vote Up', 'isNoHTML', TRUE, 'input', 200, '', '[ + ]'
    ),
    'caption_command_vote_down' => array(
      'Vote Down', 'isNoHTML', TRUE, 'input', 200, '', '[ - ]'
    ),
    'caption_command_delete' => array(
      'Delete', 'isNoHTML', TRUE, 'input', 200, 'Command for moderators.', 'Delete'
    ),
    'Messages',
    'message_dialog_input_error' => array(
      'Dialog Input Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Invalid input. Please check the field(s) "%s".'
    )
  );

  /**
   * Comments object
   * @var ACommunityComments
   */
  protected $_comments = NULL;

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
      $definitionValues = array('acommunity_comments_box');
      $ressource = $this->setRessourceData();
      if (isset($ressource->id)) {
        $access = TRUE;
        if ($ressource->type == 'group') {
          $access = $this->comments()->data()->surferHasGroupAccess;
        }
        $definitionValues[] = (int)$access;
        if ($access) {
          $currentSurferId = !empty($this->papaya()->surfer->surfer['surfer_id']) ?
            $this->papaya()->surfer->surfer['surfer_id'] : NULL;
          if (!empty($currentSurferId)) {
            $this->_cacheDefiniton = new PapayaCacheIdentifierDefinitionBoolean(FALSE);
          } else {
            $definitionValues[] = $ressource->type;
            $definitionValues[] = $ressource->id;
            $referenceParameters = $this->comments()->data()->referenceParameters();
            $parameterNames = array_merge(
              array('command', 'comment_id'), array_keys($referenceParameters)
            );
            unset($referenceParameters);
            include_once(dirname(__FILE__).'/../Cache/Identifier/Values.php');
            $values = new ACommunityCacheIdentifierValues();
            $definitionValues[] = $values->lastChangeTime(
              'comments:'.$ressource->type.'_'.$ressource->id
            );
            $this->_cacheDefiniton = new PapayaCacheIdentifierDefinitionGroup(
              new PapayaCacheIdentifierDefinitionValues($definitionValues),
              new PapayaCacheIdentifierDefinitionParameters($parameterNames, $this->paramName)
            );
          }
        } else {
          $definitionValues[] = $ressource->type;
          $definitionValues[] = $ressource->id;
        }
      }
      if (is_null($this->_cacheDefiniton)) {
        $this->_cacheDefiniton = new PapayaCacheIdentifierDefinitionGroup(
          new PapayaCacheIdentifierDefinitionValues($definitionValues)
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
    return FALSE;
  }

  /**
   * Set surfer ressource data to load corresponding surfer
   */
  public function setRessourceData() {
    $ressource = $this->comments()->data()->ressource('ressource', $this);
    if (is_null($ressource->isInvalid)) {
      $ressourceParameterNames = array(
        'surfer' => array('user_name', 'user_handle', 'surfer_handle'),
        'group' => array('group_handle'),
        'page' => array('page_id'),
        'image' => array('image_id')
      );
      list($ressourceType, $ressourceParameterValue) = $ressource->detectSourceParameterValue(
        $ressourceParameterNames
      );
      $ressource->set($ressourceType, NULL, $ressourceParameterNames, NULL, $ressourceParameterValue);
      $this->comments()->acommunityConnector()->ressource($ressource);
    }
    return $ressource;
  }

  /**
  * Get (and, if necessary, initialize) the ACommunityComments object
  *
  * @return ACommunityComments $comments
  */
  public function comments(ACommunityComments $comments = NULL) {
    if (isset($comments)) {
      $this->_comments = $comments;
    } elseif (is_null($this->_comments)) {
      include_once(dirname(__FILE__).'/../Comments.php');
      $this->_comments = new ACommunityComments();
      $this->_comments->parameterGroup($this->paramName);
      $this->_comments->data()->languageId = $this->papaya()->request->languageId;
    }
    return $this->_comments;
  }

  /**
  * Get parsed data
  *
  * @return string $result
  */
  function getParsedData() {
    $this->initializeParams();
    $this->setDefaultData();
    $this->setRessourceData();
    $this->comments()->data()->setPluginData(
      $this->data,
      array(
        'caption_dialog_text', 'caption_dialog_button', 'caption_command_reply',
        'caption_command_vote_up', 'caption_command_vote_down', 'caption_command_delete'
      ),
      array('message_dialog_input_error')
    );
    return $this->comments()->getXml();
  }
}