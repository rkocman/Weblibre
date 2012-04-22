<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

/**
 * Edit Calibre model
 *
 * @author  Radim Kocman
 */
final class EditCalibre extends BaseCalibre 
{
  
  /**
   * Get metadata path
   * @param int $id Book id
   * @return string Metadata path
   */
  private function getMetadataPath($id)
  {
    $path = dibi::query("
      SELECT b.path
      FROM books b
      WHERE b.id=%u", $id,"
    ")->fetchSingle();
    
    return $this->db."/".$path."/metadata.opf";
  }
  
  /**
   * Get value of first finded node
   * @param string $query XPath expression
   * @param DOMXPath $xpath Metadata DOMXPath instance
   * @return string Result value
   */
  private function getNodeValue($query ,$xpath)
  {
    $items = $xpath->query($query);
    if ($items->length > 0)
      return $items->item(0)->nodeValue;
    else
      return "";
  }
  
  /**
   * Get values of finded nodes
   * @param string $query XPath expression
   * @param DOMXPath $xpath Metadata DOMXPath instance
   * @return array Results in array
   */
  private function getNodesValues($query, $xpath)
  {
    $items = $xpath->query($query);
    $array = array();
    foreach($items as $item) {
      $array[] = $item->nodeValue;
    }
    return $array;
  }
  
  /**
   * Convert author(s) array into string
   * @param array $authors
   * @return string
   */
  private function convertAuthors($authors)
  {
    $result = "";
    $first = true;
    foreach ($authors as $author) {
      if (!$first) {
        $result .= " & ";
      }
      $result .= $author;
      $first = false;
    }
    return $result;
  }
  
  /**
   * Convert format of series index
   * @param string $index
   * @return string
   */
  private function convertIndex($index)
  {
    if ($index === "")
      $index = 1;
    return number_format($index, 2);
  }
  
  /**
   * Convert tags array into string
   * @param array $tags
   * @return string
   */
  private function convertTags($tags)
  {
    $result = "";
    $first = true;
    foreach ($tags as $tag) {
      if (!$first) {
        $result .= ", ";
      }
      $result .= $tag;
      $first = false;
    }
    return $result;
  }
  
  /**
   * Convert identifiers array into string
   * @param array $identifiers
   * @return string
   */
  private function convertIdentifiers($identifiers)
  {
    $result = "";
    $first = true;
    foreach ($identifiers as $key => $value) {
      if (!$first) {
        $result .= ", ";
      }
      $result .= mb_strtolower($key).":".$value;
      $first = false;
      
    }
    return $result;
  }
  
  /**
   * Convert time to Calibre time with undefined value
   * @param DateTime $time
   * @return DateTime|NULL
   */
  private function convertTime(DateTime $time)
  {
    if ($time == new DateTime('0100-12-31T23:00:00+00:00'))
      return NULL;
    else
      return $time;
  }
  
  /**
   * Check database and convert languages array into string
   * @param array $languages
   * @param int $id Book id
   * @return string
   */
  private function convertLanguages($languages, $id)
  {
    // Check linked languages
    $count = dibi::query("
      SELECT COUNT(*)
      FROM books_languages_link
      WHERE book=%u", $id,"
    ")->fetchSingle();
    if ($count == 0)
      return "";
    
    // Convert array
    $result = "";
    $first = true;
    foreach ($languages as $language) {
      if (!$first) {
        $result .= ", ";
      }
      $result .= $language;
      $first = false;
    }
    return $result;
  }

  /**
   * Get book metadata
   * @param int $id Book id
   * @return array Result in array
   */
  public function getMetadata($id)
  {
    // Get path
    $path = $this->getMetadataPath($id);
    
    // Load OPF
    $opf = new DOMDocument();
    $opf->load($path);
    $xpath = new DOMXPath($opf);
    $xpath->registerNamespace("opf", "http://www.idpf.org/2007/opf");
    $xpath->registerNamespace("dc", "http://purl.org/dc/elements/1.1/"); 
    
    //Extract metadata
    $metadata = array();    
    $metadata['title'] = $this->getNodeValue(
      "//dc:title", $xpath);
    $metadata['title_sort'] = $this->getNodeValue(
      "//opf:meta[@name='calibre:title_sort']/@content", $xpath);
    $metadata['authors'] = $this->getNodesValues(
      "//dc:creator", $xpath);
    $metadata['author_sort'] = $this->getNodeValue(
      "//dc:creator/@opf:file-as", $xpath);
    $metadata['series'] = $this->getNodeValue(
      "//opf:meta[@name='calibre:series']/@content", $xpath);
    $metadata['series_index'] = $this->getNodeValue(
      "//opf:meta[@name='calibre:series_index']/@content", $xpath);
    $metadata['rating'] = $this->getNodeValue(
      "//opf:meta[@name='calibre:rating']/@content", $xpath);
    $metadata['tags'] = $this->getNodesValues(
      "//dc:subject", $xpath);
    
    $identifiers_scheme = $this->getNodesValues(
      "//dc:identifier[not(@id)]/@opf:scheme", $xpath);
    $identifiers_value = $this->getNodesValues(
      "//dc:identifier[not(@id)]", $xpath);
    $metadata['identifiers'] = array();
    for($i = 0; $i < count($identifiers_scheme); $i++)
      $metadata['identifiers'][$identifiers_scheme[$i]] = $identifiers_value[$i];
    
    $date = $this->getNodeValue(
      "//opf:meta[@name='calibre:timestamp']/@content", $xpath);
    $metadata['date'] = new DateTime($date);
    
    $published = $this->getNodeValue(
      "//dc:date", $xpath);
    $metadata['published'] = new DateTime($published);
    
    $metadata['publisher'] = $this->getNodeValue(
      "//dc:publisher", $xpath);
    $metadata['languages'] = $this->getNodesValues(
      "//dc:language", $xpath);
    $metadata['comments'] = $this->getNodeValue(
      "//dc:description", $xpath);
    
    // Convert metadata format
    $metadata['authors'] = $this->convertAuthors($metadata['authors']);
    $metadata['series_index'] = $this->convertIndex($metadata['series_index']);
    $metadata['rating'] = ($metadata['rating'] === "")? 0 : $metadata['rating'];
    $metadata['tags'] = $this->convertTags($metadata['tags']);
    $metadata['identifiers'] = $this->convertIdentifiers($metadata['identifiers']);
    $metadata['date'] = $this->convertTime($metadata['date']);
    $metadata['published'] = $this->convertTime($metadata['published']);
    $metadata['languages'] = $this->convertLanguages($metadata['languages'], $id);
    
    return $metadata;
  }
  
  /**
   * Save edited metadata
   * @param array $values EditForm values
   * @param int $id Book id
   * @return bool
   * @throws Nette\Application\ApplicationException
   */
  public function saveMetadata($values, $id)
  {
    // Temp folder for metadata
    $path = "../temp/metadata/";
    if (!is_dir($path))
      if (!mkdir($path, 0777))
        throw new NA\ApplicationException("Unable create temp directory.");
      
    // Create unique name
    do {
      $name = $this->randomName().".opf";
      $file = realpath($path).DIRECTORY_SEPARATOR.$name;
    } while (file_exists($file));
    
    // Create OPF format
    $opf = new XMLWriter();
    if (!$opf->openURI($file))
      throw new NA\ApplicationException("Unable create opf file.");
    $this->createOPF($opf, $values);
    $opf = null;
    
    // Delete book identifiers from database
    dibi::query("
      DELETE FROM identifiers
      WHERE book=%u", $id,"
    ");
    
    // Delete book languages link from database
    dibi::query("
      DELETE FROM books_languages_link
      WHERE book=%u", $id,"
    ");
    
    // Request calibre
    $db = " --library-path ".escapeshellarg(realpath($this->db));
    $command =
      "set_metadata ".escapeshellarg($id)." "
      .escapeshellarg($file)
      .$db;
    $result = $this->execute("calibredb", $command);
    
    // Discard unnecessary
    unlink($file);
    
    // Return status
    return ($result['status'] == 0)? true : false;
  }
  
  /**
   * Create OPF format
   * @param XMLWriter $opf Matadata XMLWriter instance
   * @param array $values EditForm values
   * @return void
   */
  private function createOPF($opf, $values)
  {
    $opf->setIndent(true);
    $opf->startDocument('1.0', 'utf-8');
    
      $opf->startElement('package');
      $opf->writeAttribute('xmlns', 'http://www.idpf.org/2007/opf');
      $opf->writeAttribute('unique-identifier', 'uuid_id');
      
        $opf->startElement('metadata');
        $opf->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $opf->writeAttribute('xmlns:opf', 'http://www.idpf.org/2007/opf');
        
          // Title and title sort
          $opf->writeElement('dc:title', $values['title']);
          $opf->startElement('meta');
          $opf->writeAttribute('content', $values['title_sort']);
          $opf->writeAttribute('name', 'calibre:title_sort');
          $opf->endElement();
          
          // Authors and author sort
          $authors = preg_split('/&/', $values['authors'], -1, PREG_SPLIT_NO_EMPTY);
          if (empty($authors)) {
            $opf->startElement('dc:creator');
            $opf->writeAttribute('opf:file-as', $values['author_sort']);
            $opf->writeAttribute('opf:role', 'aut');
            $opf->endElement();
          } else {
            foreach($authors as $author) {
              $opf->startElement('dc:creator');
              $opf->writeAttribute('opf:file-as', $values['author_sort']);
              $opf->writeAttribute('opf:role', 'aut');
              $opf->text(trim($author));
              $opf->endElement();
            }
          }
          
          // Series and series index
          $opf->startElement('meta');
          $opf->writeAttribute('content', $values['series']);
          $opf->writeAttribute('name', 'calibre:series');
          $opf->endElement();
          $opf->startElement('meta');
          $opf->writeAttribute('content', $values['series_index']);
          $opf->writeAttribute('name', 'calibre:series_index');
          $opf->endElement();
          
          // Rating
          $opf->startElement('meta');
          $opf->writeAttribute('content', $values['rating']);
          $opf->writeAttribute('name', 'calibre:rating');
          $opf->endElement();
          
          // Tags
          $tags = preg_split('/,/', $values['tags'], -1, PREG_SPLIT_NO_EMPTY);
          if (empty($tags)) {
            $opf->writeElement('dc:subject', ',');
          } else {
            foreach($tags as $tag)
              $opf->writeElement('dc:subject', trim($tag));
          }
          
          // Identifiers
          $identifiers = preg_split('/,/', $values['identifiers'], -1, PREG_SPLIT_NO_EMPTY);
          foreach($identifiers as $identifier) {
            $identifier = preg_split('/:/', $identifier, -1, PREG_SPLIT_NO_EMPTY);
            if (count($identifier) != 2)
              continue;
            $opf->startElement('dc:identifier');
            $opf->writeAttribute('opf:scheme', trim($identifier[0]));
            $opf->text(trim($identifier[1]));
            $opf->endElement();
          }
          
          // Date
          $opf->startElement('meta');
          if (empty($values['date'])) {
            $opf->writeAttribute('content', '0100-12-31T23:00:00+00:00');
          } else {
            $opf->writeAttribute('content', 
              substr($values['date']->format(DateTime::W3C), 0, -6).'+00:00');
          }
          $opf->writeAttribute('name', 'calibre:timestamp');
          $opf->endElement();
          
          // Published
          if (empty($values['published'])) {
            $opf->writeElement('dc:date', '0100-12-31T23:00:00+00:00');
          } else {
            $opf->writeElement('dc:date', 
              substr($values['published']->format(DateTime::W3C), 0, -6).'+00:00');
          }
          
          // Publisher
          $opf->writeElement('dc:publisher', $values['publisher']);
          
          // Languages
          $languages = preg_split('/,/', $values['languages'], -1, PREG_SPLIT_NO_EMPTY);
          foreach($languages as $language)
            $opf->writeElement('dc:language', trim($language));
          
          // Comments
          $opf->writeElement('dc:description', $values['comments']);
        
        $opf->endElement();
      
      $opf->endElement();
    
    $opf->endDocument();
    $opf->flush();
  }
  
}