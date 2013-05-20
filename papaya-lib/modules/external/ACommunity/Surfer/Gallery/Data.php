<?php
/**
 * Advanced community surfer gallery data class to handle all sorts of related data
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
 * Base ui content data object
 */
require_once(dirname(__FILE__).'/../../Ui/Content/Data.php');

/**
 * Advanced community surfer gallery data class to handle all sorts of related data
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunitySurferGalleryData extends ACommunityUiContentData {
  
  /**
   * Ressource needs active surfer
   * @var boolean
   */
  protected $_ressourceNeedsActiveSurfer = TRUE;
  
  /**
   * Gallery database record
   *  
   * @var ACommunityContentSurferGallery
   */
  protected $_gallery = NULL;
  
  /**
   * Surfer galleries database records
   * @var object
   */
  protected $_galleries = NULL;
  
  /**
   * Media db edit object
   * @var object
   */
  protected $_mediaDBEdit = NULL;

  /**
  * Access to the surfer gallery database record data
  *
  * @param ACommunityContentSurferGallery $gallery
  * @return ACommunityContentSurferGallery
  */
  public function gallery(ACommunityContentSurferGallery $gallery = NULL) {
    if (isset($gallery)) {
      $this->_gallery = $gallery;
    } elseif (is_null($this->_gallery)) {
      include_once(dirname(__FILE__).'/../../Content/Surfer/Gallery.php');
      $this->_gallery = new ACommunityContentSurferGallery();
      $this->_gallery->papaya($this->papaya());
    }
    return $this->_gallery;
  }
  
  /**
  * Access to the surfer galleries database records data
  *
  * @param ACommunityContentSurferGalleries $galleries
  * @return ACommunityContentSurferGalleries
  */
  public function galleries(ACommunityContentSurferGalleries $galleries = NULL) {
    if (isset($galleries)) {
      $this->_galleries = $galleries;
    } elseif (is_null($this->_galleries)) {
      include_once(dirname(__FILE__).'/../../Content/Surfer/Galleries.php');
      $this->_galleries = new ACommunityContentSurferGalleries();
      $this->_galleries->papaya($this->papaya());
    }
    return $this->_galleries;
  }
  
  /**
   * Media DB Edit to save image uploads
   * 
   * @param base_mediadb_edit $mediaDBEdit
   * @return base_mediadb_edit
   */
  public function mediaDBEdit(base_mediadb_edit $mediaDBEdit = NULL) {
    if (isset($mediaDBEdit)) {
      $this->_mediaDBEdit = $mediaDBEdit;
    } elseif (is_null($this->_mediaDBEdit)) {
      include_once(PAPAYA_INCLUDE_PATH.'system/base_mediadb_edit.php');
      $this->_mediaDBEdit = new base_mediadb_edit();
    }
    return $this->_mediaDBEdit;
  }
  
}
