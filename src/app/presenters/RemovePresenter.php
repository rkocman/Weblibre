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
   * @return RemoveCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new RemoveCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  
  
  /**
   * Remove book
   * @param int $id Book id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionBook($id) 
  {
    if (!$this->calibre->checkBook($id))
      throw new NA\BadRequestException('No such book.');
    
    if ($this->calibre->removeBook($id) || !$this->calibre->checkBook($id)) {
      $this->redirect("Browse:");
    }
    else {
      $msg = $this->context->translator->translate(
        "Error: Weblibre was unable to remove this book!");
      $this->flashMessage($msg, 'error');
      $this->redirect("Book:", $id);
    }
  }
  
  /**
   * Remove format
   * @paramt int $id Format id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionFormat($id) 
  {
    if (!$this->calibre->checkFormat($id))
      throw new NA\BadRequestException('No such format.');
    
    $book = $this->calibre->getBookIdFromFormatId($id);
    if ($this->calibre->removeFormat($id)) {
      $msg = $this->context->translator->translate(
        "Selected format has been successfully removed.");
      $this->flashMessage($msg, 'ok');
    }
    else {
      $msg = $this->context->translator->translate(
        "Error: Weblibre was unable to remove selected format!");
      $this->flashMessage($msg, 'error');
    }
    
    $this->redirect("Book:", $book);
  }
  
}