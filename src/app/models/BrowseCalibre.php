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
 * Browse Calibre model
 *
 * @author  Radim Kocman
 */
final class BrowseCalibre extends BaseCalibre 
{
  
  /**
   * Set cache
   * @param string $db 
   */
  public function __construct($db) 
  {
    parent::__construct($db);
    
    // Cache
    $this->cache = new Cache($GLOBALS['container']->cacheStorage, 'browse');
  }
  
  
  /**
   * Complete search results about books
   * @param array $sequence Sequnce of books id
   * @param int $count Number of all matched books
   * @return array
   */
  private function completeSearchResults($sequence, $count) 
  {
    // Load books info
    $books = dibi::query("
      SELECT b.id, b.title, b.has_cover, b.path,
        strftime('%d. %m. %Y', b.timestamp) timestamp,
        IFNULL(r.rating, 0) rating,
        s.name series, sc.seriescount
      FROM books b 
      LEFT JOIN books_ratings_link br ON b.id = br.book
      LEFT JOIN ratings r ON br.rating = r.id
      LEFT JOIN books_series_link bs ON b.id = bs.book
      LEFT JOIN series s ON bs.series = s.id
      LEFT JOIN (
        SELECT series, COUNT(*) seriescount
        FROM books_series_link
        GROUP BY series
      ) sc ON s.id = sc.series
      WHERE b.id IN %in
    ", $sequence)->fetchAll();
    
    // Load authors
    $authors = dibi::query("
      SELECT ba.book, a.name
      FROM books_authors_link ba JOIN authors a ON ba.author = a.id
      WHERE ba.book IN %in
      ", $sequence, "
      ORDER BY ba.book, a.sort  
    ")->fetchAll();
    
    // Load formats
    $formats = dibi::query("
      SELECT id, book, format, uncompressed_size, name
      FROM data
      WHERE book IN %in
      ", $sequence, "
      ORDER BY book, format
    ")->fetchAll();
    
    // Load tags
    $tags = dibi::query("
      SELECT bt.book, t.name
      FROM books_tags_link bt JOIN tags t ON bt.tag = t.id
      WHERE bt.book IN %in
      ", $sequence, "
      ORDER BY bt.book, t.name
    ")->fetchAll();
    
    // Complete results
    $complete['count'] = $count;
    $complete['results'] = array();
    foreach($sequence as $key => $id) {
      $complete['results'][$key]['id'] = $id;
    }
    $ids = array_flip($sequence);
    foreach($books as $book){
      foreach($book as $key => $data)
        $mb[$key] = $data;
      $mb['authors'] = NULL;
      $mb['formats'] = NULL;
      $mb['tags'] = NULL;
      $complete['results'][$ids[$book['id']]] = $mb;
    }
    foreach($authors as $author) {
      $complete['results'][$ids[$author['book']]]['authors'][] = array(
        'name' => $author['name']
      );
    }
    foreach($formats as $format) {
      $complete['results'][$ids[$format['book']]]['formats'][] = array(
        'id' => $format['id'],
        'format' => $format['format'],
        'uncompressed_size' => $format['uncompressed_size'],
        'name' => $format['name']
      );
    }
    foreach($tags as $tag) {
      $complete['results'][$ids[$tag['book']]]['tags'][] = array(
        'name' => $tag['name']
      );
    }
     
    return $complete;
  }  
  
  
  /**
   * Request execute Calibre
   * @param string sortBy
   * @param string search
   * @return array
   * @throws Nette\Application\ApplicationException
   */
  private function requestExecuteCalibre($sortBy, $search) 
  {
    $db = " --library-path ".escapeshellarg(realpath($this->db));
    
    $sortCalibre =  array(
      'Author Sort' => 'author_sort --ascending',
      'Authors' => 'authors --ascending',
      'Date' => 'timestamp',
      'Identifiers' => 'identifiers --ascending',
      'Modified' => 'last_modified',
      'Published' => 'pubdate',
      'Publishers' => 'publisher --ascending',
      'Ratings' => 'rating',
      'Series' => 'series',
      'Size' => 'size --ascending',
      'Tags' => 'tags --ascending',
      'Title' => 'title --ascending'
    );
    
    $command = 
      "list" 
      ." -f uuid"
      ." -w 200"
      .(($search != NULL)? " -s \"".$this->envEscape($search)."\"" : "")
      ." --sort-by ".$sortCalibre[$sortBy]
      .$db;
    
    $result = $this->execute("calibredb", $command);
    
    if ($result['status'] != 0)
      throw new NA\ApplicationException("Unable request calibre.");
    
    // Handle output
    array_shift($result['output']); // Skip header
    $sequence = array();
    foreach ($result['output'] as $line) {
      if (!empty($line)) {
        $parseLine = explode(" ", $line);
        $sequence[] = $parseLine[0];
      }
    }
    
    return $sequence;
  }
  
  
  /**
   * Request Calibre
   * @param string sortBy
   * @param string search
   * @return array
   */
  private function requestCalibre($sortBy, $search) 
  {
    // Load cache
    if ($this->cacheResults) {
      $key = array(
        'db' => realpath($this->db),
        'sortBy' => $sortBy,
        'search' => $search
      );
      
      $sequence = $this->cache->load($key);
      if ($sequence !== NULL)
        return $sequence;
    }
    
    // Execute Calibre
    $sequence = $this->requestExecuteCalibre($sortBy, $search);
    
    // Save cache
    if ($this->cacheResults) {
      $this->cache->save($key, $sequence, array(
        Nette\Caching\Cache::FILES => $key['db'].DIRECTORY_SEPARATOR."metadata.db"
      ));
    }
    
    return $sequence;
  }
  
  
  
  /**
   * Limit records by page
   * @param int $page
   * @param int $records
   * @return string
   */
  private function limitDB($page, $records) 
  {
    return "LIMIT ".(($page-1)*$records).", ".$records;
  }
  
  /**
   * Limit calibre records
   * @param int $page
   * @param int $records
   * @param array data
   * @return array
   */
  private function limitCalibre($page, $records, $data) 
  {
    $sequence = array();
    $first = (($page-1) * $records);
    $last = $first + ($records - 1);
    for ($i = $first; $i <= $last; $i++) {
      if (isset($data[$i]))
        $sequence[] = $data[$i];
    }
    return $sequence;
  }
  
  
  
  /**
   * Get newest books
   * @param int $page
   * @param int $records
   * @return array
   */
  public function getNewestBooks($page, $records) 
  {
    $sql = "
      FROM books
      ORDER BY timestamp DESC
    ";
    
    $count = dibi::query("
      SELECT COUNT(*) count
      ".$sql."
    ")->fetch();
    
    $results = dibi::query("
      SELECT id
      ".$sql."
      ".$this->limitDB($page, $records)."
    ")->fetchAll();
    
    $sequence = array();
    foreach($results as $result) {
      $sequence[] = $result['id'];
    }
    
    return $this->completeSearchResults($sequence, $count['count']);
  }
  
  /**
   * Get all books
   * @param int $page
   * @param int $records
   * @param string $sortBy
   * @param string $search
   * @return array
   */
  public function getAllBooks($page, $records, $sortBy, $search) 
  {
    $data = $this->requestCalibre($sortBy, $search);
    
    $sequence = $this->limitCalibre($page, $records, $data);
    
    return $this->completeSearchResults($sequence, count($data));
  }
  
  
  /**
   * Get authors
   * @return array
   */
  public function getAuthors() 
  {
    return dibi::query("
      SELECT a.id, a.name, COUNT(ba.book) count, AVG(r.rating) rating
      FROM authors a
      LEFT JOIN books_authors_link ba ON a.id = ba.author
      LEFT JOIN books_ratings_link br ON ba.book = br.book
      LEFT JOIN ratings r ON br.rating = r.id AND r.rating > 0
      GROUP BY a.id, a.name
      HAVING COUNT(ba.book) > 0
      ORDER BY a.sort
    ")->fetchAll();
  }
  
  /**
   * Get author name
   * @param int $id
   * @return string
   */
  public function getAuthorName($id) 
  {
    return dibi::query("
      SELECT name
      FROM authors
      WHERE id=%u
    ", $id)->fetchSingle();
  }
  
  /**
   * Get author books
   * @param int $page
   * @param int $records
   * @param string $sortBy
   * @param int $id
   * @return array
   */
  public function getAuthorBooks($page, $records, $sortBy, $id) 
  {
    $author = $this->getAuthorName($id);
    
    $search = 'authors:"='.$author.'"';
    
    $data = $this->requestCalibre($sortBy, $search);
    
    $sequence = $this->limitCalibre($page, $records, $data);
    
    return $this->completeSearchResults($sequence, count($data));
  }
  
  
  /**
   * Get languages
   * @return array
   */
  public function getLanguages() 
  {
    return dibi::query("
      SELECT l.id, l.lang_code, COUNT(bl.book) count, AVG(r.rating) rating
      FROM languages l
      LEFT JOIN books_languages_link bl ON l.id = bl.lang_code
      LEFT JOIN books_ratings_link br ON bl.book = br.book
      LEFT JOIN ratings r ON br.rating = r.id AND r.rating > 0
      GROUP BY l.id, l.lang_code
      HAVING COUNT(bl.book) > 0
      ORDER BY l.lang_code
    ")->fetchAll();
  }
  
  /**
   * Get language name
   * @param int $id
   * @return string
   */
  public function getLanguageName($id) 
  {
    return dibi::query("
      SELECT lang_code
      FROM languages
      WHERE id=%u
    ", $id)->fetchSingle();
  }
  
  /**
   * Get language books
   * @param int $page
   * @param int $records
   * @param string $sortBy
   * @param int $id
   * @return array
   */
  public function getLanguageBooks($page, $records, $sortBy, $id) 
  {   
    $lang = $this->getLanguageName($id);
    
    $search = 'languages:"='.$lang.'"';
    
    $data = $this->requestCalibre($sortBy, $search);
    
    $sequence = $this->limitCalibre($page, $records, $data);
    
    return $this->completeSearchResults($sequence, count($data));
  }
  
  
  /**
   * Get publishers
   * @return array
   */
  public function getPublishers() 
  {
    return dibi::query("
      SELECT p.id, p.name, COUNT(bp.book) count, AVG(r.rating) rating
      FROM publishers p
      LEFT JOIN books_publishers_link bp ON p.id = bp.publisher
      LEFT JOIN books_ratings_link br ON bp.book = br.book
      LEFT JOIN ratings r ON br.rating = r.id AND r.rating > 0
      GROUP BY p.id, p.name
      HAVING COUNT(bp.book) > 0
      ORDER BY p.name
    ")->fetchAll();
  }
  
  /**
   * Get publisher name
   * @param int $id
   * @return string
   */
  public function getPublisherName($id) 
  {
    return dibi::query("
      SELECT name
      FROM publishers
      WHERE id=%u
    ", $id)->fetchSingle();
  }
  
  /**
   * Get publisher books
   * @param int $page
   * @param int $records
   * @param string $sortBy
   * @param int $id
   * @return array
   */
  public function getPublisherBooks($page, $records, $sortBy, $id) 
  {
    $pub = $this->getPublisherName($id);
    
    $search = 'publisher:"='.$pub.'"';
    
    $data = $this->requestCalibre($sortBy, $search);
    
    $sequence = $this->limitCalibre($page, $records, $data);
    
    return $this->completeSearchResults($sequence, count($data));
  }
  
  
  /**
   * Get ratings
   * @return array
   */
  public function getRatings() 
  {
    return dibi::query("
      SELECT r.id id, tab.rating rating, COUNT(tab.book) count
      FROM (
        SELECT b.id book, IFNULL(r.rating, 0) rating
        FROM books b
        LEFT JOIN books_ratings_link br ON b.id = br.book
        LEFT JOIN ratings r ON  br.rating = r.id
      ) tab
      JOIN ratings r ON tab.rating=r.rating
      GROUP BY r.id, tab.rating
      HAVING COUNT(tab.book) > 0
      ORDER BY tab.rating
    ")->fetchAll();
  }
  
  /**
   * Get rating name
   * @param int $id
   * @return string
   */
  public function getRatingName($id) 
  {
    return dibi::query("
      SELECT rating
      FROM ratings
      WHERE id=%u
    ", $id)->fetchSingle();
  }
  
  /**
   * Get rating books
   * @param int $page
   * @param int $records
   * @param string $sortBy
   * @param int $id
   * @return array
   */
  public function getRatingBooks($page, $records, $sortBy, $id) 
  {
    $rating = $this->getRatingName($id);
    
    $search = 'rating:'.($rating/2);
    
    $data = $this->requestCalibre($sortBy, $search);
    
    $sequence = $this->limitCalibre($page, $records, $data);
    
    return $this->completeSearchResults($sequence, count($data));
  }
  
  
  /**
   * Get series
   * @return array
   */
  public function getSeries() 
  {
    return dibi::query("
      SELECT s.id, s.name, COUNT(bs.book) count, AVG(r.rating) rating
      FROM series s
      LEFT JOIN books_series_link bs ON s.id = bs.series
      LEFT JOIN books_ratings_link br ON bs.book = br.book
      LEFT JOIN ratings r ON br.rating = r.id AND r.rating > 0
      GROUP BY s.id, s.name
      HAVING COUNT(bs.book) > 0
      ORDER BY s.sort
    ")->fetchAll();
  }
  
  /**
   * Get series name
   * @param int $id
   * @return string
   */
  public function getSeriesName($id) 
  {
    return dibi::query("
      SELECT name
      FROM series
      WHERE id=%u
    ", $id)->fetchSingle();
  }
  
  /**
   * Get series books
   * @param int $page
   * @param int $records
   * @param string $sortBy
   * @param int $id
   * @return array
   */
  public function getSeriesBooks($page, $records, $sortBy, $id) 
  {  
    $series = $this->getSeriesName($id);
    
    $search = 'series:"='.$series.'"';
    
    $data = $this->requestCalibre($sortBy, $search);
    
    $sequence = $this->limitCalibre($page, $records, $data);
    
    return $this->completeSearchResults($sequence, count($data));
  }
  
  
  /**
   * Get tags
   * @return array
   */
  public function getTags() 
  {
    return dibi::query("
      SELECT t.id, t.name, COUNT(bt.book) count, AVG(r.rating) rating
      FROM tags t
      LEFT JOIN books_tags_link bt ON t.id = bt.tag
      LEFT JOIN books_ratings_link br ON bt.book = br.book
      LEFT JOIN ratings r ON br.rating = r.id AND r.rating > 0
      GROUP BY t.id, t.name
      HAVING COUNT(bt.book) > 0
      ORDER BY t.name
    ")->fetchAll();
  }
  
  /**
   * Get tags name
   * @param int $id
   * @return string
   */
  public function getTagName($id) 
  {
    return dibi::query("
      SELECT name
      FROM tags
      WHERE id=%u
    ", $id)->fetchSingle();
  }
  
  /**
   * Get tag books
   * @param int $page
   * @param int $records
   * @param string $sortBy
   * @param int $id
   * @return array
   */
  public function getTagBooks($page, $records, $sortBy, $id)
  {
    $tag = $this->getTagName($id);
    
    $search = 'tags:"='.$tag.'"';
    
    $data = $this->requestCalibre($sortBy, $search);
    
    $sequence = $this->limitCalibre($page, $records, $data);
    
    return $this->completeSearchResults($sequence, count($data));
  }
  
}
