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
  public function addBooks($values) {
    
    // Temp folder for uploads
    $path = "../temp/uploads/";
    if (!is_dir($path))
      if (!mkdir($path, 0777))
        throw new NA\ApplicationException("Unable create temp directory.");
      
    // Temp folder for current upload
    do {
      $dir = $path.$this->randomName()."/";
    } while(is_dir($dir));
    if (!mkdir($dir, 0777))
      throw new NA\ApplicationException("Unable create temp directory.");
    
    // Save uploaded files
    $files = array();
    $subdirs = array();
    foreach($values as $key => $value) {
      if ($value['book']->isOk()) {
        
        // Subdir
        do {
          $subdir = $dir.$this->randomName()."/";
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
    
    // Request calibre
    $exe = escapeshellarg(realpath($this->calibre).DIRECTORY_SEPARATOR."calibredb");
    $db = " --library-path ".escapeshellarg(realpath($this->db));
    $command = $exe
      ." add ";
    foreach($files as $file)
      $command .= escapeshellarg(realpath($file))." ";
    $command .= $db;
    $result = $this->execute($command);
    
    // Discard unnecessary
    foreach($files as $file)
      unlink($file);
    foreach($subdirs as $subdir)
      rmdir($subdir);
    rmdir($dir);
    
    // Return status
    return ($result['status'] == 0)? true : false;
  }
  
}