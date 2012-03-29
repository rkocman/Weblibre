<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

/**
 * Book Calibre model
 *
 * @author  Radim Kocman
 */
final class BookCalibre extends BaseCalibre {
  
  /**
   * Get book details from library
   * @param int $id
   * @return array
   * @todo All
   */
  public function getBook($id) {
    
    // Load book info
    $result = dibi::query("
      SELECT 
        b.id, 
        b.title,
        b.author_sort,
        strftime('%d. %m. %Y', b.timestamp) date,
        strftime('%d. %m. %Y', b.pubdate) published,
        IFNULL(r.rating, 0) rating,
        s.id series_id, s.name series_name, sc.series_count,
        c.text comments
      FROM books b
      LEFT JOIN books_ratings_link br ON b.id = br.book
      LEFT JOIN ratings r ON br.rating = r.id
      LEFT JOIN books_series_link bs ON b.id = bs.book
      LEFT JOIN series s ON bs.series = s.id
      LEFT JOIN (
        SELECT series, COUNT(*) series_count
        FROM books_series_link
        GROUP BY series
      ) sc ON bs.id = sc.series
      LEFT JOIN comments c ON b.id = c.book
      WHERE b.id=%u", $id,"
    ")->fetch();;
    
    // Load authors
    $authors = dibi::query("
      SELECT a.id, a.name
      FROM books_authors_link ba 
      JOIN authors a ON ba.author = a.id
      WHERE ba.book = %u", $id,"
      ORDER BY a.sort  
    ")->fetchAll();
    
    // Load identifiers
    $identifiers = dibi::query("
      SELECT i.id, i.type, i.val
      FROM identifiers i
      WHERE i.book = %u", $id,"
      ORDER BY i.type, i.val
    ")->fetchAll();
    
    // Load languages
    $languages = dibi::query("
      SELECT l.id, l.lang_code
      FROM books_languages_link bl
      JOIN languages l ON bl.lang_code = l.id
      WHERE bl.book = %u", $id,"
      ORDER BY l.lang_code
    ")->fetchAll();
    
    // Load publishers
    $publishers = dibi::query("
      SELECT p.id, p.name
      FROM books_publishers_link bp
      JOIN publishers p ON bp.publisher = p.id
      WHERE bp.book = %u", $id,"
      ORDER BY p.name
    ")->fetchAll();
    
    // Load tags
    $tags = dibi::query("
      SELECT t.id, t.name
      FROM books_tags_link bt 
      JOIN tags t ON bt.tag = t.id
      WHERE bt.book = %u", $id,"
      ORDER BY t.name
    ")->fetchAll();
    
    // Load formats
    $formats = dibi::query("
      SELECT id, format, uncompressed_size
      FROM data
      WHERE book = %u", $id,"
      ORDER BY format
    ")->fetchAll();
    
    
    // Complete result
    $result['authors'] = $authors;
    $result['identifiers'] = $identifiers;
    $result['languages'] = $languages;
    $result['publishers'] = $publishers;
    $result['tags'] = $tags;
    
    $farray = array();
    foreach($formats as $format)
      $farray[] = array(
        'id' => $format['id'],
        'format' => $format['format'],
        'size' => $this->sizeToString($format['uncompressed_size'])
      );
    $result['formats'] = $farray;

    return $result;
  }
  
  /**
   * Check if book exist
   * @param int $id
   * @return bool
   */
  public function checkBook($id) {
    $result = dibi::query("
      SELECT b.id
      FROM books b
      WHERE b.id=%u", $id,"
    ")->fetchSingle();
    
    return ($result)? true : false;
  }
  
  /**
   * Format size into string
   * @param int $size
   * @return string
   */
  private function sizeToString($size) {
    $size = $size/1024/1024;
    
    if ($size < 0.1)
      return "< 0.1 MB";
    else
      return round($size, 1)." MB";
  }
  
}