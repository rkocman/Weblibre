<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

/**
 * View Calibre model
 *
 * @author  Radim Kocman
 */
final class ViewCalibre extends BaseCalibre 
{
  
  /** Format support */
  public static $supportedFormats = array(
    'PDF'
  );
  
  
  
  /**
   * Check selected format
   * @param int $id Format id
   * @return bool
   */
  public function checkViewFormat($id)
  {
    $format = dibi::query("
      SELECT d.format
      FROM data d
      WHERE d.id=%u", $id,"
    ")->fetchSingle();
    
    return in_array($format, self::$supportedFormats);
  }
  
  /**
   * Get format info
   * @param int $id Format id
   * @return array Result in array
   */
  public function getFormatInfo($id)
  {
    return dibi::query("
      SELECT d.format, d.book bookId, b.title bookName
      FROM data d
      JOIN books b ON d.book = b.id
      WHERE d.id=%u", $id,"
    ")->fetch();
  }
  
}