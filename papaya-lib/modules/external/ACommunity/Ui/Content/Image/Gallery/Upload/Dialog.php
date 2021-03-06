<?php
/**
 * Advanced community image gallery upload dialog
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
 * Advanced community image gallery upload dialog
 *
 * @package Papaya-Modules
 * @subpackage External-ACommunity
 */
class ACommunityUiContentImageGalleryUploadDialog extends PapayaUiControlCommandDialog {

  /**
  * Comments data
  * @var ACommunitySurferGalleryUploadData
  */
  protected $_data = NULL;

  /**
  * Current error message.
  * @var string
  */
  protected $_errorMessage = NULL;

  /**
   * Name of image field
   * @var string
   */
  protected $_imageFieldName = 'image';

  /**
   * Get/set image gallery upload data
   *
   * @param ACommunityImageGalleryUploadData $data
   * @return ACommunityImageGalleryUploadData
   */
  public function data(ACommunityImageGalleryUploadData $data = NULL) {
    if (isset($data)) {
      $this->_data = $data;
    }
    return $this->_data;
  }

  /**
  * Get/set error message
  * @var string $errorMessage
  */
  public function errorMessage($errorMessage = NULL) {
    if (isset($errorMessage)) {
      PapayaUtilConstraints::assertString($errorMessage);
      $this->_errorMessage = $errorMessage;
    }
    return $this->_errorMessage;
  }

  /**
  * Create dialog
  *
  * @see PapayaUiControlCommandDialog::createDialog()
  * @return PapayaUiDialog
  */
  public function createDialog() {
    $buttonCaption = $this->data()->captions['dialog_button'];

    $dialog = new PapayaUiDialog();

    $dialog->papaya($this->papaya());
    $dialog->parameterGroup($this->parameterGroup());
    $dialog->parameters($this->parameters());
    $dialog->action($this->data()->reference()->getRelative());
    $dialog->hiddenFields()->merge(
      array('command' => 'upload')
    );
    $dialog->caption = NULL;

    include_once(dirname(__FILE__).'/../../../../Dialog/Field/Input/File.php');
    $dialog->fields[] = $field = new ACommunityUiDialogFieldInputFile(
      $this->data()->captions['dialog_image'],
      $this->_imageFieldName,
      TRUE
    );
    $field->setMandatory(TRUE);
    $field->setId('dialogGalleryImage');
    $dialog->buttons[] = new PapayaUiDialogButtonSubmit($buttonCaption);

    $this->callbacks()->onExecuteSuccessful = array($this, 'callbackUploadImage');
    $this->callbacks()->onExecuteFailed = array($this, 'callbackShowError');
    return $dialog;
  }


  /**
  * Upload image on sucessful execution
  *
  * @param object $context
  * @param PapayaUiDialog $dialog
  */
  public function callbackUploadImage($context, $dialog) {
    $ressource = $this->data()->owner->ressource();
    $filter = array('ressource_type' => $ressource->type, 'ressource_id' => $ressource->id);
    $ressourceParameters = reset($ressource->parameters());
    if (!empty($ressourceParameters['folder_id'])) {
      $filter['folder_id'] = $ressourceParameters['folder_id'];
    } else {
      $filter['parent_folder_id'] = 0;
    }
    $this->data()->galleries()->load($filter, 1);
    $error = NULL;
    if (count($this->data()->galleries()) > 0) {
      $gallery = reset($this->data()->galleries()->toArray());
      $folderId = !empty($gallery['folder_id']) ? $gallery['folder_id'] : NULL;
      $parameterGroup = $this->data()->owner->parameterGroup();

      if (!empty($folderId)) {
        if (isset($_FILES[$parameterGroup]) &&
            isset($_FILES[$parameterGroup]['name'][$this->_imageFieldName]) &&
            isset($_FILES[$parameterGroup]['tmp_name'][$this->_imageFieldName])) {

          if (!($_FILES[$parameterGroup]['error'][$this->_imageFieldName] > 0)) {

            $allowedExtensions = array('gif', 'jpeg', 'jpg', 'png', 'zip');
            $extension = strtolower(
              end(explode('.', $_FILES[$parameterGroup]['name'][$this->_imageFieldName]))
            );
            if (in_array($extension, $allowedExtensions)) {

              $allowedImageTypes = array(
                "image/gif", "image/jpeg", "image/jpg", "image/pjpeg", "image/x-png", "image/png"
              );
              $type = $_FILES[$parameterGroup]['type'][$this->_imageFieldName];

              $allowedPackageTypes = array(
                "application/zip"
              );

              if (in_array($type, $allowedPackageTypes)) {
                $zip = new ZipArchive;
                if ($zip->open($_FILES[$parameterGroup]['tmp_name'][$this->_imageFieldName]) == TRUE) {
                  $tmpName = tempnam(PAPAYA_PATH_MEDIADB_IMPORT, 'acommunity-images-upload-');
                  unlink($tmpName);
                  mkdir($tmpName);
                  $extracted = $zip->extractTo($tmpName);
                  $zip->close();
                  if ($extracted) {
                    $folder = end(explode('/', $tmpName));
                    include_once(PAPAYA_INCLUDE_PATH.'system/papaya_mediadb.php');
                    $papayaMediaDB = new papaya_mediadb();
                    $result = $papayaMediaDB->importLocalFolder(
                      $folder, $folderId, $this->data()->currentSurferId()
                    );
                    if ($result) {
                      $added = TRUE;
                    } else {
                      $error = 'dialog_error_media_db';
                    }
                  }
                  unlink($tmpName);
                  rmdir($tmpName);
                }
              } elseif (!in_array($type, $allowedImageTypes)) {
                $error = 'dialog_error_file_type';
              }

              if (in_array($type, $allowedImageTypes)) {
                $mediaDBEdit = $this->data()->mediaDBEdit();
                $added = $mediaDBEdit->addFile(
                  $_FILES[$parameterGroup]['tmp_name'][$this->_imageFieldName],
                  $_FILES[$parameterGroup]['name'][$this->_imageFieldName],
                  $folderId,
                  $this->data()->currentSurferId()
                );
              } elseif (!in_array($type, $allowedPackageTypes)) {
                $error = 'dialog_error_file_type';
              }

              if (empty($added)) {
                $error = 'dialog_error_media_db';
              } else {
                if ($gallery['parent_folder_id'] == 0) {
                  $lastChangeRessource = $ressource->type.'_gallery_images:folder_base:'.
                    $ressource->type.'_'.$ressource->id;
                } else {
                  $lastChangeRessource = $ressource->type.'_gallery_images:folder_'.$folderId.':'.
                    $ressource->type.'_'.$ressource->id;
                }
                $this->data()->setLastChangeTime($lastChangeRessource);
                $href = $this->data()->reference()->get();
                $GLOBALS['PAPAYA_PAGE']->sendHTTPStatus(301);
                @header("Location: ".$href);
                printf(
                  '<html><head><meta http-equiv="refresh" content="0; URL=%s"></head></html>',
                  papaya_strings::escapeHTMLChars($href)
                );
                exit;
              }
            } else {
              $error = 'dialog_error_file_extension';
            }
          } else {
            $error = 'dialog_error_upload';
          }
        } else {
          $error = 'dialog_error_no_upload_file';
        }
      } else {
        $error = 'dialog_error_no_folder';
      }
    } else {
      $error = 'dialog_error_no_folder';
    }
    if (!empty($error)) {
      $this->errorMessage($this->data()->messages[$error]);
    }
  }

  /**
  * Show error message
  *
  * @param object $context
  * @param PapayaUiDialog $dialog
  */
  public function callbackShowError($context, $dialog) {
    $errorMessage = $this->errorMessage();
    if (empty($errorMessage)) {
      $this->errorMessage(
        sprintf(
          $this->data()->messages['dialog_input_error'],
          implode(', ', $dialog->errors()->getSourceCaptions())
        )
      );
    }
  }
}