<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application\UI;

/**
 * Cover library presenter
 *
 * @author     Radim Kocman
 */
final class CoverPresenter extends SignedPresenter
{
 
  /** @var CoverCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return void
   */
  public function getCalibre() {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new CoverCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  /** @var img */
  private $cover;
  
  /**
   * Default action
   * @param int/string $id
   * @param string $size 
   */
  public function actionDefault($id, $size) {
    if (!$this->calibre->checkCover($id))
      $this->redirect('this', array('id' => 'none'));
    
    $this->cover = $this->calibre->loadCover($id, $size);
  }
  
  /**
   * Render default
   */
  public function renderDefault() {
    $this->template->cover = $this->cover;
  }

}
