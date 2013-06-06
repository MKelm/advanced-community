<?php
/**
 * Advanced community comments ranking box
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
 * Basic box class
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_actionbox.php');

/**
 * Advanced community comments ranking box
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunityCommentsRankingBox extends base_actionbox implements PapayaPluginCacheable {

  /**
   * Parameter prefix name
   * @var string $paramName
   */
  public $paramName = 'accr';

  /**
   * Edit fields
   * @var array $editFields
   */
  public $editFields = array(
    'comments_per_page' => array(
      'Comments Per Page', 'isNum', TRUE, 'input', 30, '0 for all comments', 10
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
      include_once(dirname(__FILE__).'/../../Cache/Identifier/Values.php');
      $values = new ACommunityCacheIdentifierValues();
      $this->_cacheDefiniton = new PapayaCacheIdentifierDefinitionGroup(
        new PapayaCacheIdentifierDefinitionValues(
          'acommunity_comments_ranking_box', $values->lastChangeTime('comments')
        ),
        new PapayaCacheIdentifierDefinitionParameters(
          array('comments_page'), $this->paramName
        )
      );
    }
    return $this->_cacheDefiniton;
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
      include_once(dirname(__FILE__).'/../../Comments.php');
      $this->_comments = new ACommunityComments();
      $this->_comments->parameterGroup($this->paramName);
      $this->_comments->data()->languageId = $this->papaya()->request->languageId;
      $this->_comments->data()->mode = 'ranking';
      $this->_comments->module = $this;
    }
    return $this->_comments;
  }

  /**
   * Get parsed data
   *
   * @return string $result XML
   */
  public function getParsedData() {
    $this->initializeParams();
    $this->setDefaultData();
    $this->comments()->data()->setPluginData($this->data);
    return $this->comments()->getXml();
  }
}