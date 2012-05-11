<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Caching\Cache;
use Nette\Application as NA;
use Nette\Diagnostics\Debugger;

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
  protected $useXvfb;
  
  /** @var bool */
  protected $cacheResults;
  
  /** @var Nette\Caching\Cache */
  protected $cache;
  
  /**
   * Connect to database and save variables
   * @param string $db Database path
   */
  public function __construct($db) 
  {
    if (!dibi::isConnected())
      dibi::connect(array(
        'driver'   => 'sqlite3',
        'database' => $db."metadata.db",
        'profiler' => TRUE
      ));
    
    $this->calibre = $GLOBALS['wconfig']['calibre'];
    $this->db = $db;
    $this->env = $GLOBALS['wconfig']['env'];
    $this->useXvfb = $GLOBALS['wconfig']['useXvfb'];
    $this->cacheResults = $GLOBALS['wconfig']['caheResults'];
  }
  
  /**
   * Random name
   * @return string
   */
  protected function randomName() 
  {
    return md5(uniqid(rand(), true));
  }
  
  /**
   * Calibre search escaping
   * @param string $param Parameter externaly in double quotes
   * @return string Escaped parameter
   * @example For search 'authors:"=myA"' pass only myA as argument
   */
  protected function calibreEscape($param)
  {
    return addcslashes($param, '"');
  }
  
  /**
   * Environment dependent parameter escaping
   * @param string $param Parameter externaly in double quotes
   * @return string Escaped parameter
   * @example For command 'echo "myData"' pass only myData as argument
   */
  protected function envEscape($param) 
  {
    if ($this->env == "windows") {
      
      // Split parameter into array of char
      $arr = preg_split("//u", $param, -1, PREG_SPLIT_NO_EMPTY);
      $result = "";
      $count = 0;
      
      // For all chars in array
      foreach ($arr as $char) {
        // If backslash increase count
        if ($char == "\\") $count++;
        
        // If not backslash
        else {
          // If char is " escape all backleshes and add one
          if ($char == '"') $count = $count*2 + 1; 
          
          // Pass counted backslashes into result
          for ($i = 0; $i < $count; $i++) $result .= "\\";
          $count = 0;
          
          // Pass char into result
          $result .= $char;
        }
      }
      // Escape ended backslashes
      for ($i = 0; $i < $count*2; $i++) $result .= "\\";
        
      return $result;
      
    } else {
      
      return addcslashes($param, '"$`\\');
      
    }
  } 
  
  /**
   * Windows command line escaping
   * @param string $command Parameter
   * @return string Escaped parameter
   */
  private function winCmdEscape($command) 
  {
    $metachar = 
      array("^",  "(",  ")",  "%",  "!",  "\"",  "<",  ">",  "&",  "|");
    $escaped = 
      array("^^", "^(", "^)", "^%", "^!", "^\"", "^<", "^>", "^&", "^|");
    
    return str_replace($metachar, $escaped, $command);
  }
  
  /**
   * Execute on Widnows
   * @param string $exe Executable file path
   * @param string $command Additional parameters
   * @return array Result with return code and output
   * @throws Nette\Application\ApplicationException
   */
  private function executeOnWidnows($exe, $command) 
  {
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
    $content = "chcp 65001\r\n".$exe." ".$this->winCmdEscape($command);
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
   * @param string $exe Executable file path
   * @param string $command Additional parameters
   * @return array Result with return code and output
   */
  private function executeOnUnix($exe, $command) 
  {
    // Exec
    putenv('LANG=en_US.UTF-8');
    exec($exe." ".$command, $output, $status);
    
    return array(
      'status' => $status,
      'output' => $output
    );
  }
  
  /**
   * Execute command
   * @param string $exe Executable file selection ["calibredb", "ebook-convert"]
   * @param string $command Additional parameters
   * @return array Result with return code and output
   * @throws Nette\Application\ApplicationException
   */
  protected function execute($exe, $command) 
  {
    // Increase PHP time limit
    set_time_limit(120);
    
    // Expand exe
    $exePath = realpath($this->calibre).DIRECTORY_SEPARATOR;
    switch($exe) {
      case "calibredb":
        $exe = escapeshellarg($exePath."calibredb");
        break;
      case "ebook-convert":
        $exe = escapeshellarg($exePath."ebook-convert");
        if ($this->env == "unix" && $this->useXvfb)
          $exe = "xvfb-run ".$exe;
        break;
      default:
        throw new NA\ApplicationException("Bad execute command.");
        break;
    }
    
    // Disconnect database
    $connection = dibi::getConnection();
    dibi::disconnect();
    
    // Execute by environment
    if ($this->env == "windows")
      $result = $this->executeOnWidnows($exe, $command);
    else
      $result = $this->executeOnUnix($exe, $command);
    
    // Reconnect database
    dibi::setConnection($connection);
    
    // Log bad execution
    if ($result['status'] != 0)
      Debugger::log(
        "Unable request Calibre: (".$result['status'].") "
        .$exe." ".$command
      );
    
    // Return result
    return $result;
  }
  
  
  
  /**
   * Check book
   * @param int $id Book id
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
   * @param int $id Book id
   * @return string Book name
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
