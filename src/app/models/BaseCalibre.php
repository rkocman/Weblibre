<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Caching\Cache;

/**
 * Base communication model with Calibre
 *
 * @author  Radim Kocman
 */
abstract class BaseCalibre extends Nette\Object 
{ 
  /** @var string */
  protected $calibre;
  
  /** @var string */
  protected $db;
  
  /** @var string */
  protected $env;
  
  /** @var bool */
  protected $cacheResults;
  
  /** @var Nette\Caching\Cache */
  protected $cache;
  
  /**
   * Connect to database and save variables
   * @param string $db 
   */
  public function __construct($db) {
    if (!dibi::isConnected())
      dibi::connect(array(
        'driver'   => 'sqlite3',
        'database' => $db."metadata.db",
        'profiler' => TRUE
      ));
    
    $this->calibre = $GLOBALS['wconfig']['calibre'];
    $this->db = $db;
    $this->env = $GLOBALS['wconfig']['env'];
    $this->cacheResults = $GLOBALS['wconfig']['caheResults'];
  }
  
  /**
   * Random name
   * @return string
   */
  protected function randomName() {
    return md5(uniqid(rand(), true));
  }
  
  /**
   * Execute on Widnows
   * @param string $command
   * @throws DirError
   * @return array
   */
  private function executeOnWidnows($command) {
    $path = "../temp/winexec/";
    
    // Create dir
    if (!is_dir($path))
      if (!mkdir($path, 0777))
        throw new Exception("Unable create temp directory");
    
    // Create .bat file
    do {
      $name = $this->randomName().".bat";
      $comp = $path.$name;
    } while (file_exists($comp));
    $f = fopen($comp, 'w');
    $content = "chcp 65001\r\n".$command;
    fwrite($f, $content);
    fclose($f);
    
    // Exec
    exec(
      escapeshellarg(realpath($path).DIRECTORY_SEPARATOR.$name), 
      $output, $status);
    
    // Discard unnecessary
    unlink($comp);
    for ($i = 0; $i < 6; $i++)
      unset($output[$i]);
    
    return array(
      'status' => $status,
      'output' => $output
    );
  }
  
  /**
   * Execute on Unix
   * @param string $command
   * @return array
   */
  private function executeOnUnix($command) {
    //setlocale(LC_ALL, "en_US.UTF-8");
    // Exec
    exec($command, $output, $status);
    
    // Discard unnecessary
    unset($output[0]);
    
    return array(
      'status' => $status,
      'output' => $output
    );
  }
  
  /**
   * Execute command
   * @param string $command
   * @return array
   */
  protected function execute($command) {
    if ($this->env == "windows")
      return $this->executeOnWidnows($command);
    else
      return $this->executeOnUnix($command);
  }
}
