<?php
/**
 * Advanced community image gallery upload box
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
 * Advanced community image gallery upload box
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunityImageGalleryUploadBox extends base_actionbox implements PapayaPluginCacheable {

  /**
   * Parameter prefix name
   * @var string $paramName
   */
  public $paramName = 'acig';

  /**
   * Edit fields
   * @var array $editFields
   */
  public $editFields = array(
    'Captions',
    'caption_dialog_image' => array(
      'Dialog Image/ZIP', 'isNoHTML', TRUE, 'input', 200, '', 'Image or ZIP'
    ),
    'caption_dialog_button' => array(
      'Dialog Button', 'isNoHTML', TRUE, 'input', 200, '', 'Add'
    ),
    'Messages',
    'message_dialog_input_error' => array(
      'Dialog Input Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Invalid input. Please check the field(s) "%s".'
    ),
    'message_dialog_error_no_folder' => array(
      'Dialog No Folder Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Could not find images folder.'
    ),
    'message_dialog_error_no_upload_file' => array(
      'Dialog No Upload File Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Could not find upload file.'
    ),
    'message_dialog_error_upload' => array(
      'Dialog Upload Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Upload error.'
    ),
    'message_dialog_error_file_extension' => array(
      'Dialog File Extension Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Wrong file extension.'
    ),
    'message_dialog_error_file_type' => array(
      'Dialog File Type Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Wrong file type.'
    ),
    'message_dialog_error_media_db' => array(
      'Dialog Media DB Error', 'isNoHTML', TRUE, 'input', 200, '',
      'Could not comple upload process in Media DB.'
    )
  );

  /**
   * Gallery upload object
   * @var ACommunityImageGalleryUpload
   */
  protected $_upload = NULL;

  /**
   * Cache definition
   * @var PapayaCacheIdentifierDefinition
   */
  protected $_cacheDefinition = NULL;

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
      $this->_cacheDefinition = $definition;
    } elseif (NULL == $this->_cacheDefinition) {
      $ressource = $this->setRessourceData();
      if (isset($ressource->id)) {
        $this->_cacheDefinition = new PapayaCacheIdentifierDefinitionBoolean(FALSE);
      } else {
        $this->_cacheDefinition = new PapayaCacheIdentifierDefinitionValues(
          array('acommunity_image_gallery_upload')
        );
      }
    }
    return $this->_cacheDefinition;
  }

  /**
   * Set ressource data to get surfer
   */
  public function setRessourceData() {
    if (is_null($this->_ressource)) {
      $ressource = $this->upload()->ressource();
      $ressource->pointer = 0;
      $command = $ressource->getSourceParameter('command');
      if ($command != 'delete_folder') {
        $filterParameterNames = array(
          'surfer' => array('surfer_handle', 'folder_id', 'offset'),
          'group' => array('group_handle', 'folder_id', 'offset')
        );
      } else {
        $filterParameterNames = array(
          'surfer' => array('surfer_handle'), 'group' => array('group_handle')
        );
      }
      $ressource->set(
        $ressource->type,
        NULL,
        $filterParameterNames,
        array('surfer' => 'enlarge', 'group' => 'enlarge'),
        $ressource->handle,
        $ressource->type == 'group' ? TRUE : 'is_selected'
      );
      $this->_ressource = $ressource;
    }
    return $this->_ressource;
  }

  /**
  * Get (and, if necessary, initialize) the ACommunityImageGalleryUpload object
  *
  * @return ACommunityImageGalleryUpload $upload
  */
  public function upload(ACommunityImageGalleryUpload $upload = NULL) {
    if (isset($upload)) {
      $this->_upload = $upload;
    } elseif (is_null($this->_upload)) {
      include_once(dirname(__FILE__).'/../Upload.php');
      $this->_upload = new ACommunityImageGalleryUpload();
      $this->_upload->parameterGroup($this->paramName);
      $this->_upload->data()->languageId = $this->papaya()->request->languageId;
      $this->_upload->module = $this;
    }
    return $this->_upload;
  }

  /**
   * Get parsed data
   *
   * @return string $result XML
   */
  public function getParsedData() {
    $this->initializeParams();
    $this->setRessourceData();
    $this->setDefaultData();
    $captionNames = array('caption_dialog_image', 'caption_dialog_button');
    $messageNames = array(
      'message_dialog_input_error', 'message_dialog_error_no_folder',
      'message_dialog_error_no_upload_file', 'message_dialog_error_upload',
      'message_dialog_error_file_extension', 'message_dialog_error_file_type',
      'message_dialog_error_media_db'
    );
    $this->upload()->data()->setPluginData(
      $this->data, $captionNames, $messageNames
    );
    return $this->upload()->getXml();
  }
}