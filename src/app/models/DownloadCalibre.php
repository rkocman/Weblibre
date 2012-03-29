<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Caching\Cache;

/**
 * Download Calibre model
 *
 * @author  Radim Kocman
 */
final class DownloadCalibre extends BaseCalibre 
{

  /**
   * Get file path
   * @param int $id
   * @return string|NULL
   */
  public function getPath($id) {
    $path = dibi::query("
      SELECT b.path, d.name, d.format
      FROM data d
      JOIN books b ON d.book = b.id
      WHERE d.id=%u ", $id,"
    ")->fetch();
    
    if (empty($path))
      return NULL;
    
    return $this->db."/"
      .$path['path']."/"
      .$path['name']."."
      .strtolower($path['format']);
  }
  
}
