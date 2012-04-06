<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application as NA;

/**
 * Remove Calibre model
 *
 * @author  Radim Kocman
 */
final class RemoveCalibre extends BaseCalibre 
{
  
  /**
   * Check book
   * @param int $id
   * @return bool
   */
  public function checkBook($id) 
  {
    $sql = dibi::query("
      SELECT b.id 
      FROM books b
      WHERE b.id=%u", $id,"
    ")->fetchSingle();
    return ($sql)? true : false;
  }
  
  /**
   * Check format
   * @param int $id
   * @return bool
   */
  public function checkFormat($id) 
  {
    $sql = dibi::query("
      SELECT d.id 
      FROM data d
      WHERE d.id=%u", $id,"
    ")->fetchSingle();
    return ($sql)? true : false;
  }
  
  /**
   * Get book id from format id
   * @param int $id
   * @return int
   */
  public function getBookIdFromFormatId($id) 
  {
    return dibi::query("
      SELECT d.book
      FROM data d
      WHERE d.id=%u", $id,"
    ")->fetchSingle();
  }
  
  
  
  /**
   * Remove book
   * @param $id
   * @return bool
   */
  public function removeBook($id) 
  {  
    // Request calibre
    $db = " --library-path ".escapeshellarg(realpath($this->db));
    $command =
      "remove ".escapeshellarg($id)
      .$db;
    $result = $this->execute("calibredb", $command);
    
    // Return status
    return ($result['status'] == 0)? true : false;
  }
  
  /**
   * Remove format
   * @param $id
   * @return bool
   */
  public function removeFormat($id) 
  {  
    // Get format info
    $format = dibi::query("
      SELECT d.book, d.format
      FROM data d
      WHERE d.id=%u", $id,"
    ")->fetch();
    
    // Request calibre
    $db = " --library-path ".escapeshellarg(realpath($this->db));
    $command =
      "remove_format "
      .escapeshellarg($format['book'])." "
      .escapeshellarg($format['format'])
      .$db;
    $result = $this->execute("calibredb", $command);
    
    // Return status
    return ($result['status'] == 0)? true : false;
  }
  
}