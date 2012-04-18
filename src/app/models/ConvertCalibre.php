<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application as NA;

/**
 * Convert Calibre model
 *
 * @author  Radim Kocman
 */
final class ConvertCalibre extends BaseCalibre 
{

  /** Format support */
  public static $supportedFormats = array(
    'EPUB', 'FB2', 'HTML', 'HTMLZ', 'LIT', 'LRF',
    'MOBI', 'PDB', 'PDF',  'PMLZ',  'RB',  'RTF',
    'SNB',  'TCR', 'TXT',  'TXTZ'
  );
  
  
  
  /**
   * Check if book has convertable format
   * @param int $id
   * @return bool
   */
  public function checkConvertable($id)
  {
    $count = dibi::query("
      SELECT COUNT(*)
      FROM data d
      WHERE d.book=%u", $id,"
      AND d.format IN %in", self::$supportedFormats,"
    ")->fetchSingle();
    
    return ($count != 0)? true : false;
  }
  
  /**
   * Get book's convertable formats
   * @param int $id
   * @return array
   */
  public function getFormats($id)
  {
    $formats = dibi::query("
      SELECT d.format
      FROM data d
      WHERE d.book=%u", $id,"
      AND d.format IN %in", self::$supportedFormats,"
      ORDER BY d.format
    ")->fetchAll();
    
    $result = array();
    foreach($formats as $format)
      $result[] = $format['format'];
    
    return $result;
  }
  
  /**
   * Convert book
   * @param array $values
   * @param int $id
   * @return bool
   * @throws Nette\Application\ApplicationException
   */
  public function convert($values, $id)
  {
    // Input file
    $dc = new DownloadCalibre($this->db);
    $format = dibi::query("
      SELECT d.id
      FROM data d
      WHERE d.book=%u", $id,"
      AND d.format=%s", $values['input_format'],"
    ")->fetchSingle();
    $input_file = realpath($dc->getPath($format));

    // Ouput file
    $path = "../temp/conversion/";
    if (!is_dir($path))
      if (!mkdir($path, 0777))
        throw new NA\ApplicationException("Unable create temp directory.");
    do {
      $name = $this->randomName().".".$values['output_format'];
      $output_file = realpath($path).DIRECTORY_SEPARATOR.$name;
    } while (file_exists($output_file));

    // Execute conversion
    if(!$this->convertExecute($input_file, $output_file, $values))
      return false;
    
    // Add format
    $ac = new AddCalibre($this->db);
    if(!$ac->addFormatFile($output_file, $id))
      return false;
    
    // Discard unnecessary
    unlink($output_file);
    
    return true;
  }
  
  /**
   * Executing conversion
   * @param string $input_file
   * @param string $output_file
   * @param string $values
   * @return bool
   */
  private function convertExecute($input_file, $output_file, $values)
  {
    $command = 
      escapeshellarg($input_file)." "
      .escapeshellarg($output_file)
      
      // Metadata
      .' --title "'.$this->envEscape($values['metadata']['title']).'"'
      .' --authors "'.$this->envEscape($values['metadata']['authors']).'"'
      .' --author-sort "'.$this->envEscape($values['metadata']['author_sort']).'"'
      .' --publisher "'.$this->envEscape($values['metadata']['publisher']).'"'
      .' --tags "'.$this->envEscape($values['metadata']['tags']).'"'
      .' --series "'.$this->envEscape($values['metadata']['series']).'"'
      .' --series-index "'.$this->envEscape($values['metadata']['series_index']).'"'
      .' --comments "'.$this->envEscape($values['metadata']['comments']).'"'
    ;
    
    $result = $this->execute("ebook-convert", $command);
    return ($result['status'] == 0)? true : false;
  }
  
}