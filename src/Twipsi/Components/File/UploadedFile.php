<?php
declare(strict_types=1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Components\File;

use Twipsi\Components\File\FileItem;
use Twipsi\Support\Str;
use Twipsi\Components\File\Exceptions\FileException;

class UploadedFile extends FileItem
{
  /**
  * The name of the file.
  */
  protected string $name;

  /**
  * The type of the file.
  */
  protected string $type;

  /**
  * The path to the file.
  */
  protected string $path;

  /**
  * Errors on uploading.
  */
  protected int $error;

  /**
  * Size fo the file.
  */
  protected int $size;

  /**
  * Possible error messages.
  */
  protected const ERROR_MSG = [
            UPLOAD_ERR_OK => 'There where no errors uploading file [%s]',
            UPLOAD_ERR_INI_SIZE => 'The file [%s] exceeds your upload_max_filesize ini directive (limit is %d KiB).',
            UPLOAD_ERR_FORM_SIZE => 'The file [%s] exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL => 'The file [%s] was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file [%s] could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
            'UNKNOWN' => 'The file [%s] was not uploaded due to an unknown error.',
        ];

  /**
  * Construct our file item.
  */
  public function __construct(array $data)
  {
    $this->name = $this->getBaseName($data['name']);
    $this->type = $data['type'] ?: 'application/octet-stream';
    $this->path = $data['tmp_name'];
    $this->size = $data['size'];
    $this->error = $data['error'] ?: \UPLOAD_ERR_OK;

    parent::__construct($this->path);
  }

  /**
  * Get the original name given.
  */
  public function getOriginalName() : string
  {
    return $this->name;
  }

  /**
  * Get the original type given from the name.
  */
  public function getOriginalExtension() :? string
  {
    $ext = explode('.', $this->name);
    return end($ext);
  }

  /**
  * Get the files mime type.
  */
  public function getOriginalMimeType() :? string
  {
    return $this->type;
  }

  /**
  * Get error.
  */
  public function getError() : int
  {
    return $this->error;
  }

  /**
  * Get the file size.
  */
  public function getSize() : int
  {
    return $this->size;
  }

  /**
  * Check if the uplaoded file is valid.
  */
  public function isValid() : bool
  {
    return $this->error === \UPLOAD_ERR_OK && is_uploaded_file($this->getPath());
  }

  /**
  * Move the file to provided location.
  */
  public function move(string $location, string $name = null) : FileItem
  {
    if($this->isValid()) {
      return parent::move($location, $name);
    }

    throw new FileException($this->getErrorMessage());
  }

  /**
  * Get the error message provided.
  */
  public function getErrorMessage() : string
  {
    if(in_array($code = $this->getError(), self::ERROR_MSG) ) {
      return sprintf(self::ERROR_MSG[$code], $this->getOriginalName(), $this->getMaxFileSize());
    }

    $msg = array_key_last(self::ERROR_MSG);
    return sprintf(self::ERROR_MSG[$msg], $this->getOriginalName());
  }

  /**
  * Get the max upload file size
  */
  public function getMaxFileSize() : int
  {
    $post = $this->calcFileSize(ini_get('post_max_size'));
    $upload = $this->calcFileSize(ini_get('upload_max_filesize'));

    return min($post, $upload);
  }

  /**
  * Get and convert php file size
  */
  private function calcFileSize(string $size) : int
  {
    switch (Str::hay($size)->last()) {
      case 'T' :
        return (int)$size * 1099511627776;
      case 'G' :
        return (int)$size * 1073741824;
      case 'M' :
        return (int)$size * 1048576;
      case 'K' :
        return (int)$size * 1024;
      default:
        return (int)$size;
    }
  }

}
