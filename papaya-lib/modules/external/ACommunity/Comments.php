<?php
/**
 * Advanced community comments containing comments tree
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
require_once(dirname(__FILE__).'/Ui/Content.php');

/**
 * Advanced community comments containing comments tree
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunityComments extends ACommunityUiContent {

  /**
   * Ui content comment dialog
   * @var ACommunityUiContentCommentDialog
   */
  protected $_uiContentCommentDialog = NULL;

  /**
   * Ui comments list control
   * @var ACommunityUiContentCommentsList
   */
  protected $_uiCommentsList = NULL;

  /**
   * Get/set comments data
   *
   * @param ACommunityCommentsData $data
   * @return ACommunityCommentsData
   */
  public function data(ACommunityCommentsData $data = NULL) {
    if (isset($data)) {
      $this->_data = $data;
    } elseif (is_null($this->_data)) {
      include_once(dirname(__FILE__).'/Comments/Data.php');
      $this->_data = new ACommunityCommentsData();
      $this->_data->papaya($this->papaya());
      $this->_data->owner = $this;
    }
    return $this->_data;
  }

  /**
   * Perform commands by parameter
   */
  public function performCommands() {
    $command = $this->parameters()->get('command', '');
    if (!empty($command)) {
      $commentId = $this->parameters()->get('comment_id', 0);
      if ($commentId > 0) {
        $lastChangeTime = 0;
        if ($command == 'vote_up' || $command == 'vote_down') {
          $votingCookieData = $this->data()->votingCookie();
          if (empty($votingCookieData[$commentId])) {
            $comment = clone $this->data()->comment();
            $comment->load($commentId);
            $vote = 0;
            switch ($command) {
              case 'vote_up':
                $comment->assign(array('votes_score' => $comment['votes_score'] + 1));
                if ($comment->save()) {
                  $vote = 1;
                }
                break;
              case 'vote_down':
                $comment->assign(array('votes_score' => $comment['votes_score'] - 1));
                if ($comment->save()) {
                  $vote = -1;
                }
                break;
            }
            if ($vote != 0) {
              $votingCookieData[$commentId] = $vote;
              $this->data()->votingCookie($votingCookieData);
              $lastChangeTime = time();
            }
          }
        } elseif ($command == 'delete' &&
                  ($this->data()->surferIsModerator() ||
                   $this->ressource()->validSurfer === 'is_selected' ||
                   $this->ressource()->validSurfer === 'is_owner')) {
          $comment = clone $this->data()->comment();
          $comment->load($commentId);
          if ($comment->delete()) {
            $lastChangeTime = time();
          }
        }
        if ($lastChangeTime > 0) {
          $result1 = $this->data()->setLastChangeTime(
            'comments:'.$this->ressource()->type.'_'.$this->ressource()->id
          );
          $result2 = $this->data()->setLastChangeTime('comments');
          $this->parameters()->set('command', 'reply');
          $this->parameters()->set('comment_id', 0);
          return $result1 && $result2;
        }
      }
      return FALSE;
    }
  }

  /**
  * Create dom node structure of the given object and append it to the given xml
  * element node.
  *
  * @param PapayaXmlElement $parent
  */
  public function appendTo(PapayaXmlElement $parent) {
    $request = $this->parameters()->get('request', NULL);
    if ($request == 'thumbnail_link' || $request == 'video_link') {
      // output for ajax request "thumbnail_link" or "video_link" / supports messages and comments
      $ressource = $this->ressource();
      $ident = $this->parameters()->get('ident');
      $from = $this->parameters()->get('from');
      $identRequestHandle = $request == 'thumbnail_link' ?
        'request_a_thumbnail_link_image' : 'request_a_video_link';
      $checkIdent = md5(
        $identRequestHandle.':'.$from.':surfer_'.$this->data()->currentSurferId().
        ':'.$ressource->type.'_'.$ressource->id
      );
      if (!empty($ident) && $ident == $checkIdent) {
        $url = $this->parameters()->get('url');
        if (!empty($url) && $url != '{URL}') {
          if ($from == 'comments') {
            $filterRessource = $from.':'.$ressource->type.'_'.$ressource->id;
          } elseif ($from == 'messages') {
            $filterRessource = $from.':surfer_'.$this->data()->currentSurferId().
              ':'.$ressource->type.'_'.$ressource->id;
          }
          include_once(dirname(__FILE__).'/Filter/Text/Extended.php');
          if ($request == 'thumbnail_link') {
            $filter = new ACommunityFilterTextExtended(
              NULL, $filterRessource, $this->papaya()->session, $ident, NULL
            );
            $filter->addThumbnailLink($url);
            $link = reset($filter->thumbnailLinks());
          } else {
            $filter = new ACommunityFilterTextExtended(
              NULL, $filterRessource, $this->papaya()->session, NULL, $ident
            );
            $filter->addVideoLink($url);
            $link = reset($filter->videoLinks());
          }
          if (!empty($link)) {
            $parent->appendXml($link);
          }
        } else {
          // unset session values by type request
          unset($this->papaya()->session->values[$ident]);
        }
      }
    } elseif (isset($this->ressource()->id)) {
      // comments output
      if ($this->data()->mode == 'list') {
        $this->performCommands();
        $comments = $parent->appendElement('acommunity-comments');
        $currentSurferId = $this->data()->currentSurferId();
        if (!empty($currentSurferId)) {

          $dom = new PapayaXmlDocument();
          $dom->appendElement('dialog');
          $this->uiContentCommentDialog()->appendTo($dom->documentElement);
          $resetDialog = $this->parameters()->get('reset_dialog', 0);
          if (!empty($resetDialog)) {
            $dom = new PapayaXmlDocument();
            $dom->appendElement('dialog');
            $this->uiContentCommentDialog(NULL, TRUE)->appendTo($dom->documentElement);
          }
          $xml = '';
          foreach ($dom->documentElement->childNodes as $node) {
            $xml .= $node->ownerDocument->saveXml($node);
          }
          $comments->appendXml($xml);

          $errorMessage = $this->uiContentCommentDialog()->errorMessage();
          if (!empty($errorMessage)) {
            $comments->appendElement(
              'dialog-message', array('type' => 'error'), $errorMessage
            );
          }
        }
      } else {
        $comments = $parent->appendElement('acommunity-comments-ranking');
      }
      $this->uiCommentsList()->appendTo($comments);
      if ($this->data()->mode == 'list') {
        $comments->appendElement(
          'command',
          array(
            'name' => PapayaUtilStringXml::escape($this->parameters()->get('command', 'reply')),
            'comment_id' => PapayaUtilStringXml::escapeAttribute($this->parameters()->get('comment_id', 0))
          )
        );
      }
    }
  }

  /**
  * Access to the ui content comment dialog control
  *
  * @param ACommunityUiContentCommentDialog $uiContentCommentDialog
  * @param boolean $reset
  * @return ACommunityUiContentCommentDialog
  */
  public function uiContentCommentDialog(
           ACommunityUiContentCommentDialog $uiContentCommentDialog = NULL,
           $reset = FALSE
         ) {
    if (isset($uiContentCommentDialog)) {
      $this->_uiContentCommentDialog = $uiContentCommentDialog;
    } elseif (is_null($this->_uiContentCommentDialog) || $reset == TRUE) {
      include_once(dirname(__FILE__).'/Ui/Content/Comment/Dialog.php');
      $this->_uiContentCommentDialog = new ACommunityUiContentCommentDialog(
        $this->data()->comment()
      );
      $this->_uiContentCommentDialog->data($this->data());
      $this->_uiContentCommentDialog->parameters($this->parameters());
      $this->_uiContentCommentDialog->parameterGroup($this->parameterGroup());
    }
    return $this->_uiContentCommentDialog;
  }

  /**
  * Access to the ui comments list
  *
  * @param ACommunityUiContentCommentsList $uiCommentsList
  * @return ACommunityUiContentCommentsList
  */
  public function uiCommentsList(
           ACommunityUiContentCommentsList $uiCommentsList = NULL
         ) {
    if (isset($uiCommentsList)) {
      $this->_uiCommentsList = $uiCommentsList;
    } elseif (is_null($this->_uiCommentsList)) {
      include_once(dirname(__FILE__).'/Ui/Content/Comments/List.php');
      $this->_uiCommentsList = new ACommunityUiContentCommentsList();
      $this->_uiCommentsList->papaya($this->papaya());
      $this->_uiCommentsList->data($this->data());
    }
    return $this->_uiCommentsList;
  }
}