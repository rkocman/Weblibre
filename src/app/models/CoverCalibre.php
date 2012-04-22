<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Caching\Cache;
use Nette\Application as NA;

/**
 * Cover Calibre model
 *
 * @author  Radim Kocman
 */
final class CoverCalibre extends BaseCalibre 
{
  
  /**
   * Set cache
   * @param string $db 
   */
  public function __construct($db) 
  {
    parent::__construct($db);
    
    // Cache
    $this->cache = new Cache($GLOBALS['container']->cacheStorage, 'cover');
  }
  
  
  /**
   * Get img path
   * @param int|string $id Book id or "none"
   * @return string Cover path
   */
  private function getPath($id) 
  {
    // No img
    if ($id === "none")
      return './img/no-cover.jpg';
    
    // Other
    $book = dibi::query("
      SELECT path
      FROM books
      WHERE id=%u
    ", $id)->fetchSingle();
    return $this->db.'/'.$book.'/cover.jpg';
  }
  
  /**
   * Check if cover exist
   * @param int|string $id Book id or "none"
   * @return bool
   */
  public function checkCover($id) 
  { 
    return file_exists($this->getPath($id));
  }
  
  /**
   * Prepare img to selected max size
   * @param int|string $id Book id or "none"
   * @param int $width Cover width
   * @param int $height Cover height
   * @return img 
   * @throws Nette\Application\ApplicationException
   */
  private function prepareImg($id, $width, $height) 
  {
    $file = $this->getPath($id);
    
    $img = imagecreatefromjpeg($file);
    $size = getimagesize($file);
    
    if (!$size[0] || !$size[1] || !$img)
      throw new NA\ApplicationException("Unable load image.");
    
    // Skip resize if source is smaller
    if ($size[0] <= $width && $size[1] <= $height)
      return $img;
    
    // Set max size
    if ($size[0] > $size[1]) {
      $imgw = $width;
      $imgh = round($size[1]*$width/$size[0]);
    } else {
      $imgw = round($size[0]*$height/$size[1]);
      $imgh = $height;
    }
    
    // Resize
    $img2 = imagecreatetruecolor($imgw, $imgh);
    imagecopyresampled($img2, $img, 0, 0, 0, 0, $imgw, $imgh, $size[0], $size[1]);
    
    return $img2;
  }
  
  /**
   * Get img with selected max size
   * @param int|string $id Book id or "none"
   * @param string $size Selected size ["browse", "book"]
   * @return img
   */
  private function getImg($id, $size) 
  {
    switch ($size) {
      default:
      case "browse":
        $width = 120;
        $height = 120;
        break;
      case "book":
        $width = 400;
        $height = 400;
        break;
    }
    return $this->prepareImg($id, $width, $height);
  }
  
  /**
   * Load cover
   * @param int|string $id Book id or "none"
   * @param string $size Selected size ["browse", "book"]
   */
  public function loadCover($id, $size) 
  {
    $path = realpath($this->getPath($id));
    
    $key = array(
      'path' => $path,
      'size' => $size
    );
    
    // Load cache
    $cover = $this->cache->load($key);
    if ($cover !== NULL)
      return $cover;
    
    // Get img
    $img = $this->getImg($id, $size);
    
    // Convert to jpeg
    ob_start();
    imagejpeg($img, NULL, 95);
    $cover = ob_get_contents();
    ob_end_clean();
    imagedestroy($img);
    
    // Save cache
    $this->cache->save($key, $cover, array(
      Nette\Caching\Cache::FILES => $path
    ));
    
    return $cover;
  }
  
  /**
   * Load time
   * @param int|string $id Book id or "none"
   * @return time
   */
  public function loadTime($id) 
  {
    return filemtime($this->getPath($id));
  }
  
}
