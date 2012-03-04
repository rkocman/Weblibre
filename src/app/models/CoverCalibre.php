<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

/**
 * Cover Calibre model
 *
 * @author  Radim Kocman
 */
final class CoverCalibre extends BaseCalibre 
{ 

  /**
   * Check if cover exist
   * @param int/string $id
   * @return bool
   */
  public function checkCover($id) {
    
    if ($id == "none")
      return true;
    else
      return false;
    
  }
  
  /**
   * Load cover
   * @param int/string $id
   * @param string $size 
   */
  public function loadCover($id, $size) {
    
    return NULL;
    
  }
  
}
