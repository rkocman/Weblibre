<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application as NA;

/**
 * Add Calibre model
 *
 * @author  Radim Kocman
 */
final class AddCalibre extends BaseCalibre 
{

  /**
   * Add books into library
   * @param array $values
   * @return bool
   * @throws Nette\Application\ApplicationException
   */
  public function addBooks($values) 
  {
    return $this->addUploaded($values);
  }
  
  /**
   * Add format into library
   * @param array $values
   * @param int $id
   * @return bool
   * @throws Nette\Application\ApplicationException
   */
  public function addFormat($values, $id) 
  {
    return $this->addUploaded($values, $id);
  }
  
  /**
   * Add uploaded into library
   * @param array $values 
   * @param int $id
   * @return bool
   * @throws Nette\Application\ApplicationException
   */
  private function addUploaded($values, $id=NULL) 
  {
    // Temp folder for uploads
    $path = "../temp/uploads/";
    if (!is_dir($path))
      if (!mkdir($path, 0777))
        throw new NA\ApplicationException("Unable create temp directory.");
    
    // Save uploaded files
    $files = array();
    $subdirs = array();
    foreach($values as $key => $value) {
      if ($value['book']->isOk()) {
        
        // Subdir
        do {
          $subdir = $path.$this->randomName()."/";
        } while (is_dir($subdir));
        if (!mkdir($subdir, 0777))
          throw new NA\ApplicationException("Unable create temp directory.");
        
        // Move file
        $name = $value['book']->getSanitizedName();
        move_uploaded_file($value['book']->getTemporaryFile(), $subdir.$name);
        $subdirs[] = $subdir;
        $files[] = $subdir.$name;
        
      }
    }
    
    // If not empty
    if (!empty($files)) {
    
      // Request calibre
      $db = " --library-path ".escapeshellarg(realpath($this->db));
      if ($id === NULL) { // Add books
        $command = "add ";
        foreach($files as $file)
          $command .= escapeshellarg(realpath($file))." ";
      } else { // Add format
        $command = "add_format ".escapeshellarg($id)." ";
        $command .= escapeshellarg(realpath($files[0]));
      }
      $command .= $db;
      $result = $this->execute("calibredb", $command);
      dump($result);
    
    }
    
    // Discard unnecessary
    foreach($files as $file)
      unlink($file);
    foreach($subdirs as $subdir)
      rmdir($subdir);
    
    // Return status
    if (!empty($files))
      return ($result['status'] == 0)? true : false;
    else
      return false;
  }
  
  
  
  /**
   * Add empty book into library
   * @return bool
   */
  public function addEmptyBook() 
  {  
    // Request calibre
    $db = " --library-path ".escapeshellarg(realpath($this->db));
    $command =
      "add --empty"
      .$db;
    $result = $this->execute("calibredb", $command);
    
    // Return status
    return ($result['status'] == 0)? true : false;
  }
  
  
  
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
   * Get book name
   * @param int $id
   * @return string
   */
  public function getBookName($id) 
  {
    return dibi::query("
      SELECT b.title
      FROM books b
      WHERE b.id=%u", $id,"
    ")->fetchSingle();
  }
  
}