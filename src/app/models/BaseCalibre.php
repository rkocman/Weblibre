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
   * @return array
   * @throws Nette\Application\ApplicationException
   */
  private function executeOnWidnows($command) {
    $path = "../temp/winexec/";
    
    // Create dir
    if (!is_dir($path))
      if (!mkdir($path, 0777))
        throw new NA\ApplicationException("Unable create temp directory.");
    
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
    for ($i = 0; $i < 5; $i++)
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
    
    return array(
      'status' => $status,
      'output' => $output
    );
  }
  
  /**
   * Execute command
   * @param string $command
   * @return array
   * @throws Nette\Application\ApplicationException
   */
  protected function execute($command) {
    set_time_limit(120);
    
    // Disconnect database
    $connection = dibi::getConnection();
    dibi::disconnect();
    
    // Execute by environment
    if ($this->env == "windows")
      $result = $this->executeOnWidnows($command);
    else
      $result = $this->executeOnUnix($command);
    
    // Reconnect database
    dibi::setConnection($connection);
    
    // Return result
    return $result;
  }
}
