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
      .escapeshellarg($output_file);

    // Metadata
    $command .=
      ' --title "'.$this->envEscape($values['metadata']['title']).'"'
      .' --authors "'.$this->envEscape($values['metadata']['authors']).'"'
      .' --author-sort "'.$this->envEscape($values['metadata']['author_sort']).'"'
      .' --publisher "'.$this->envEscape($values['metadata']['publisher']).'"'
      .' --tags "'.$this->envEscape($values['metadata']['tags']).'"'
      .' --series "'.$this->envEscape($values['metadata']['series']).'"'
      .' --series-index "'.$this->envEscape($values['metadata']['series_index']).'"'
      .' --comments "'.$this->envEscape($values['metadata']['comments']).'"';

    // Look & Feel
    $sub = $values['look'];
    $command .=
      (($sub['disable_font_rescaling'])?  ' --disable-font-rescaling' : '')
      .' --base-font-size "'.$this->envEscape($sub['base_font_size']).'"'
      .' --font-size-mapping "'.$this->envEscape($sub['font_size_mapping']).'"'
      .' --minimum-line-height "'.$this->envEscape($sub['minimum_line_height']).'"'
      .' --line-height "'.$this->envEscape($sub['line_height']).'"'
      .' --input-encoding "'.$this->envEscape($sub['input_encoding']).'"'
      .(($sub['remove_paragraph_spacing'])?  ' --remove-paragraph-spacing' : '')
      .' --remove-paragraph-spacing-indent-size "'.$this->envEscape($sub['remove_paragraph_spacing_indent_size']).'"'
      .(($sub['insert_blank_line'])?  ' --insert-blank-line' : '')
      .' --insert-blank-line-size "'.$this->envEscape($sub['insert_blank_line_size']).'"'
      .' --change-justification "'.$this->envEscape($sub['change_justification']).'"'
      .(($sub['smarten_punctuation'])?  ' --smarten-punctuation' : '')
      .(($sub['unsmarten_punctuation'])?  ' --unsmarten-punctuation' : '')
      .(($sub['asciiize'])?  ' --asciiize' : '')
      .(($sub['keep_ligatures'])?  ' --keep-ligatures' : '')
      .(($sub['linearize_tables'])?  ' --linearize-tables' : '')
      .' --extra-css "'.$this->envEscape($sub['extra_css']).'"'
      .' --filter-css "'.$this->envEscape(
        (($sub['filter_css_fonts'])?  'font-family,' : '')
        .(($sub['filter_css_margins'])?  'margin,' : '')
        .(($sub['filter_css_padding'])?  'padding,' : '')
        .(($sub['filter_css_floats'])?  'float,' : '')
        .(($sub['filter_css_colors'])?  'color,' : '')
        .$sub['filter_css_other']
      ).'"';
    
    // Heuristic Processing
    $sub = $values['heuristic'];
    if ($sub['enable_heuristics']) {
      $command .=
        ' --enable-heuristics'
        .(($sub['unwrap_lines'])?  '' : ' --disable-unwrap-lines')
        .' --html-unwrap-factor "'.$this->envEscape($sub['html_unwrap_factor']).'"'
        .(($sub['markup_chapter_headings'])?  '' : ' --disable-unwrap-lines')
        .(($sub['renumber_headings'])?  '' : ' --disable-renumber-headings')
        .(($sub['delete_blank_paragraphs'])?  '' : ' --disable-delete-blank-paragraphs')
        .(($sub['format_scene_breaks'])?  '' : ' --disable-format-scene-breaks')
        .' --replace-scene-breaks "'.$this->envEscape($sub['replace_scene_breaks']).'"'
        .(($sub['disable_dehyphenate'])?  '' : ' --disable-dehyphenate')
        .(($sub['italicize_common_cases'])?  '' : ' --disable-italicize-common-cases')
        .(($sub['fix_indents'])?  '' : ' --disable-fix-indents');
    }
    
    // Page Setup
    $sub = $values['setup'];
    $command .=
      ' --output-profile "'.$this->envEscape($sub['output_profile']).'"'
      .' --input-profile "'.$this->envEscape($sub['input_profile']).'"'
      .' --margin-left "'.$this->envEscape($sub['margin_left']).'"'
      .' --margin-top "'.$this->envEscape($sub['margin_top']).'"'
      .' --margin-right "'.$this->envEscape($sub['margin_right']).'"'
      .' --margin-bottom "'.$this->envEscape($sub['margin_bottom']).'"';
            
    // Structure Detection
    $sub = $values['structure'];
    $command .=
      ' --chapter "'.$this->envEscape($sub['chapter']).'"'
      .' --chapter-mark "'.$this->envEscape($sub['chapter_mark']).'"'
      .(($sub['remove_first_image'])?  ' --remove-first-image' : '')
      .(($sub['remove_fake_margins'])?  '' : ' --disable-remove-fake-margins')
      .(($sub['insert_metadata'])?  ' --insert-metadata' : '')
      .' --page-breaks-before "'.$this->envEscape($sub['page_breaks_before']).'"';
    
    // Table of Contents
    $sub = $values['table'];
    $command .= 
      (($sub['use_auto_toc'])?  ' --use-auto-toc' : '')
      .(($sub['no_chapters_in_toc'])?  ' --no-chapters-in-toc' : '')
      .(($sub['duplicate_links_in_toc'])?  ' --duplicate-links-in-toc' : '')
      .' --max-toc-links "'.$this->envEscape($sub['max_toc_links']).'"'
      .' --toc-threshold "'.$this->envEscape($sub['toc_threshold']).'"'
      .' --toc-filter "'.$this->envEscape($sub['toc_filter']).'"'
      .((!empty($sub['level1_toc']))? (' --level1-toc "'.$this->envEscape($sub['level1_toc']).'"') : '')
      .((!empty($sub['level2_toc']))? (' --level2-toc "'.$this->envEscape($sub['level2_toc']).'"') : '')
      .((!empty($sub['level3_toc']))? (' --level3-toc "'.$this->envEscape($sub['level3_toc']).'"') : '');
    
    // Search & Replace
    $sub = $values['search'];
    $command .= 
      ' --sr1-search "'.$this->envEscape($sub['sr1_search']).'"'
      .' --sr1-replace "'.$this->envEscape($sub['sr1_replace']).'"'
      .' --sr2-search "'.$this->envEscape($sub['sr2_search']).'"'
      .' --sr2-replace "'.$this->envEscape($sub['sr2_replace']).'"'
      .' --sr3-search "'.$this->envEscape($sub['sr3_search']).'"'
      .' --sr3-replace "'.$this->envEscape($sub['sr3_replace']).'"';
    
    // FB2 Input
    $sub = $values['input_fb2'];
    if ($values['input_format'] == 'FB2') {
      $command .= 
        (($sub['no_inline_fb2_toc'])?  ' --no-inline-fb2-toc' : '');
    }
    
    // PDF Input
    $sub = $values['input_pdf'];
    if ($values['input_format'] == 'PDF') {
      $command .= 
        ' --unwrap-factor "'.$this->envEscape($sub['unwrap_factor']).'"'
        .(($sub['no_images'])?  ' --no-images' : '');
    }
    
    // TXT (TXTZ) Input
    $sub = $values['input_txt'];
    if ($values['input_format'] == 'TXT' || $values['input_format'] == 'TXTZ') {
      $command .= 
        ' --paragraph-type "'.$this->envEscape($sub['paragraph_type']).'"'
        .' --formatting-type "'.$this->envEscape($sub['formatting_type']).'"'
        .(($sub['preserve_spaces'])?  ' --preserve-spaces' : '')
        .(($sub['txt_in_remove_indents'])?  ' --txt-in-remove-indents' : '')
        .(($sub['markdown_disable_toc'])?  ' --markdown-disable-toc' : '');
    }
    
    // EPUB Output
    $sub = $values['output_epub'];
    if ($values['output_format'] == 'EPUB') {
      $command .= 
        (($sub['dont_split_on_page_breaks'])?  ' --dont-split-on-page-breaks' : '')
        .(($sub['no_default_epub_cover'])?  ' --no-default-epub-cover' : '')
        .(($sub['no_svg_cover'])?  ' --no-svg-cover' : '')
        .(($sub['epub_flatten'])?  ' --epub-flatten' : '')
        .(($sub['preserve_cover_aspect_ratio'])?  ' --preserve-cover-aspect-ratio' : '')
        .' --flow-size "'.$this->envEscape($sub['flow_size']).'"';
    }
    
    // FB2 Output
    $sub = $values['output_fb2'];
    if ($values['output_format'] == 'FB2') {
      $command .= 
        ' --sectionize "'.$this->envEscape($sub['sectionize']).'"'
        .' --fb2-genre "'.$this->envEscape($sub['fb2_genre']).'"';
    }
    
    // HTMLZ Output
    $sub = $values['output_htmlz'];
    if ($values['output_format'] == 'HTMLZ') {
      $command .= 
        ' --htmlz-css-type "'.$this->envEscape($sub['htmlz_css_type']).'"'
        .' --htmlz-class-style "'.$this->envEscape($sub['htmlz_class_style']).'"';
    }
    
    // MOBI Output
    $sub = $values['output_mobi'];
    if ($values['output_format'] == 'MOBI') {
      $command .= 
        (($sub['no_inline_toc'])?  ' --no-inline-toc' : '')
        .' --toc-title "'.$this->envEscape($sub['toc_title']).'"'
        .(($sub['mobi_toc_at_start'])?  ' --mobi-toc-at-start' : '')
        .(($sub['mobi_ignore_margins'])?  ' --mobi-ignore-margins' : '')
        .(($sub['prefer_author_sort'])?  ' --prefer-author-sort' : '')
        .(($sub['dont_compress'])?  ' --dont-compress' : '')
        .' --personal-doc "'.$this->envEscape($sub['personal_doc']).'"'
        .(($sub['share_not_sync'])?  ' --share-not-sync' : '');
    }
    
    // PDB Output
    $sub = $values['output_pdb'];
    if ($values['output_format'] == 'PDB') {
      $command .= 
        ' --format "'.$this->envEscape($sub['format']).'"'
        .' --pdb-output-encoding "'.$this->envEscape($sub['pdb_output_encoding']).'"'
        .(($sub['inline_toc'])?  ' --inline-toc' : '');
    }
    
    // PDF Output
    $sub = $values['output_pdf'];
    if ($values['output_format'] == 'PDF') {
      $command .= 
        ' --paper-size "'.$this->envEscape($sub['paper_size']).'"'
        .' --orientation "'.$this->envEscape($sub['orientation']).'"'
        .' --custom-size "'.$this->envEscape($sub['custom_size']).'"'
        .(($sub['preserve_cover_aspect_ratio'])?  ' --preserve-cover-aspect-ratio' : '');
    }
    
    // PMLZ Output
    $sub = $values['output_pmlz'];
    if ($values['output_format'] == 'PMLZ') {
      $command .= 
        ' --pml-output-encoding "'.$this->envEscape($sub['pml_output_encoding']).'"'
        .(($sub['inline_toc'])?  ' --inline-toc' : '')
        .(($sub['full_image_depth'])?  ' --full-image-depth' : '');
    }
    
    // RB Output
    $sub = $values['output_rb'];
    if ($values['output_format'] == 'RB') {
      $command .= 
        (($sub['inline_toc'])?  ' --inline-toc' : '');
    }
    
    // SNB Output
    $sub = $values['output_snb'];
    if ($values['output_format'] == 'SNB') {
      $command .= 
        (($sub['snb_insert_empty_line'])?  ' --snb-insert-empty-line' : '')
        .(($sub['snb_dont_indent_first_line'])?  ' --snb-dont-indent-first-line' : '')
        .(($sub['snb_hide_chapter_name'])?  ' --snb-hide-chapter-name' : '')
        .(($sub['snb_full_screen'])?  ' --snb-full-screen' : '');
    }
    
    // TXT (TXTZ) Output
    $sub = $values['output_txt'];
    if ($values['output_format'] == 'TXT' || $values['output_format'] == 'TXTZ') {
      $command .= 
        ' --txt-output-encoding "'.$this->envEscape($sub['txt_output_encoding']).'"'
        .' --newline "'.$this->envEscape($sub['newline']).'"'
        .' --txt-output-formatting "'.$this->envEscape($sub['txt_output_formatting']).'"'
        .(($sub['inline_toc'])?  ' --inline-toc' : '')
        .' --max-line-length "'.$this->envEscape($sub['max_line_length']).'"'
        .(($sub['force_max_line_length'])?  ' --force-max-line-length' : '')
        .(($sub['keep_links'])?  ' --keep-links' : '')
        .(($sub['keep_image_references'])?  ' --keep-image-references' : '')
        .(($sub['keep_color'])?  ' --keep-color' : '');
    }
    
    $result = $this->execute("ebook-convert", $command);
    return ($result['status'] == 0)? true : false;
  }
  
}