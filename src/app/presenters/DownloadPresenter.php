<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application\UI;
use Nette\Application as NA;

/**
 * Download presenter
 *
 * @author     Radim Kocman
 */
final class DownloadPresenter extends SignedPresenter
{
 
  /** @var DownloadCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return void
   */
  public function getCalibre() {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new DownloadCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  /**
   * Handle download request
   * @param int $id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionDefault($id) {
    $path = $this->calibre->getPath($id);
    
    if ($path === NULL)
      throw new NA\BadRequestException('No such file.');
    
    FileDownload::getInstance()
      ->setSourceFile(realpath($path))
      ->download();
    
    $this->terminate();  
  }
  
}