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
 * Add new books presenter
 *
 * @author     Radim Kocman
 */
final class BookPresenter extends SignedPresenter 
{
  
  /** @var array */
  private $book;
  
  
  /** @var BookCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return BookCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new BookCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  
  
  /**
   * Add data into template
   * @return void
   */
  protected function beforeRender() 
  {
    parent::beforeRender();
    
    // Add navigation
    $this->addNavigation('Library', 'Browse:');
    
    // Set add layout
    $this->setLayout('book');
  }
  
  
  
  /**
   * Book's details
   * @param int $id 
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionDefault($id) 
  {
    if (!$this->calibre->checkBook($id))
      throw new NA\BadRequestException('No such book.');
    
    $this->book = $this->calibre->getBook($id);
  }
  
  /**
   * Book's details render
   */
  public function renderDefault() 
  {
     // Add navigation
    $this->addNavigation($this->book['title'], NULL, false);
    
    $this->template->book = $this->book;
  }
  
}