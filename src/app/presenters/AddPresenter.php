<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

/**
 * Add new books presenter
 *
 * @author     Radim Kocman
 */
final class AddPresenter extends SignedPresenter
{

	/**
   * Add data into template
   * @return void
   */
  protected function beforeRender() {
    parent::beforeRender();
    
    // Add navigation
    $this->addNavigation('Add new books', '');
  }

}
