<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application\UI;

/**
 * Cover presenter
 *
 * @author     Radim Kocman
 */
final class CoverPresenter extends SignedPresenter 
{
 
  /** 
   * CoverCalibre Model
   * @var CoverCalibre
   */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return CoverCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new CoverCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  /** 
   * Current cover
   * @var img
   */
  private $cover;
  
  /** 
   * Current cover timestamp
   * @var time 
   */
  private $time;
  
  /**
   * Default action
   * @param int|string $id Book id
   * @param string $size Selected size ["browse", "book"]
   */
  public function actionDefault($id, $size) 
  {
    if (!$this->calibre->checkCover($id))
      $this->redirect('this', array('id' => 'none'));
    
    $this->cover = $this->calibre->loadCover($id, $size);
    $this->time = $this->calibre->loadTime($id);
  }
  
  /**
   * Render cover
   * @return void
   */
  public function renderDefault() 
  {
    $http = $this->getHttpResponse();
    $http->setHeader('Cache-Control', 'private, max-age=10800, pre-check=10800');
    $http->setHeader('Pragma', 'private');
    $http->setHeader('Expires', date(DATE_RFC822, strtotime(" 2 day")));
    $http->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $this->time).' GMT');
    
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
      (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $this->time)) 
    {
      $http->setCode(Nette\Http\IResponse::S304_NOT_MODIFIED);
      $this->terminate();
    }
    
    $this->template->cover = $this->cover;
  }

}
