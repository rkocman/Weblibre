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
 * Remove presenter
 *
 * @author     Radim Kocman
 */
final class RemovePresenter extends SignedPresenter 
{
  
  /** @var RemoveCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return void
   */
  public function getCalibre() {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new RemoveCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  
  
  /**
   * Remove book
   * @param int $id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionBook($id) {
    if (!$this->calibre->checkBook($id))
      throw new NA\BadRequestException('No such book.');
    
    $this->calibre->removeBook($id);
    
    $this->redirect("Browse:");
  }
  
  /**
   * Remove format
   * @paramt int $id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionFormat($id) {
    if (!$this->calibre->checkFormat($id))
      throw new NA\BadRequestException('No such format.');
    
    $book = $this->calibre->getBookIdFromFormatId($id);
    $this->calibre->removeFormat($id);
    
    $this->redirect("Book:", array('id' => $book));
  }
  
}