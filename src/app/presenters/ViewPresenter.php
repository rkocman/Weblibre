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
 * View presenter
 *
 * @author     Radim Kocman
 */
final class ViewPresenter extends SignedPresenter 
{
  /** @var string **/
  private $format;
  
  /** @var string **/
  private $bookId;
  
  /** @var string **/
  private $bookName;
  
  
  
  /** @var ViewCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return ViewCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new ViewCalibre($data['db']);
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
    $this->setLayout('view');
  }
  
  
  
  /**
   * View actions
   * @param int $id Format id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionDefault($id)
  {
    if (!$this->calibre->checkViewFormat($id))
      throw new NA\BadRequestException('Bad format.');
    
    $info = $this->calibre->getFormatInfo($id);
    $this->format = $info['format'];
    $this->bookId = $info['bookId'];
    $this->bookName = $info['bookName'];
  }
  
  /**
   * Render view
   * @param int $id Format id
   * @return void
   */
  public function renderDefault($id)
  {
    // Add navigation
    $view = $this->context->translator->translate("View");
    $this->addNavigation($this->bookName, 'Book:', false, $this->bookId);
    $this->addNavigation($view.' '.$this->format, NULL, false);
    
    // Info into template
    $this->template->id = $id;
    $this->template->format = $this->format;
    $this->template->bookName = $this->bookName;
  }
  
}