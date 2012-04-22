<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application\UI;
use Nette\Application as NA;

/**
 * Convert presenter
 *
 * @author     Radim Kocman
 */
final class ConvertPresenter extends SignedPresenter 
{
  /** @var string **/
  private $bookName;
  
  /** @var array **/
  private $metadata;
  
  
  
  /** @var ConvertCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return ConvertCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new ConvertCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  /** @var EditCalibre */
  private $calibreEditModel = NULL;
  
  /**
   * Connect Calibre edit model
   * @return EditCalibre
   */
  public function getCalibreEdit() 
  {
    if (!isset($this->calibreEditModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreEditModel = new EditCalibre($data['db']);
    }
    
    return $this->calibreEditModel;
  }
  
  
  /**
   * Add data into template
   * @return void
   */
  protected function beforeRender() 
  {
    parent::beforeRender();
    
    // Add navigation
    $this->addNavigation('Library', 'Browse:');
    
    // Set add layout
    $this->setLayout('convert');
  }
  
  
  
  /**
   * Convert form
   * @return Nette\Application\UI\Form
   */
  protected function createComponentConvertForm()
  {
    $id = $this->getParam('id');
    
    $form = new UI\Form;
    $form->setTranslator($this->context->translator);
    
    // Select part
    $form->addSelect('input_format', 'Input format')
      ->setItems($this->calibre->getFormats($id), false);
    $form->addSelect('output_format', 'Output format')
      ->setItems(ConvertCalibre::$supportedFormats, false);
    
    // Metadata
    $sub = $form->addContainer('metadata');
    $sub->addText('title', 'Title');
    $sub->addText('authors', 'Author(s)');
    $sub->addText('author_sort', 'Author sort');
    $sub->addText('publisher', 'Publisher');
    $sub->addText('tags', 'Tags');
    $sub->addText('series', 'Series');
    $sub->addText('series_index', 'Number')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Series index must be a numeric value.");
    $sub->addTextArea('comments', 'Comments');
    
    // Look & Feel
    $sub = $form->addContainer('look');
    $sub->addCheckbox('disable_font_rescaling', 'Disable font size rescaling');
    $sub->addText('base_font_size', 'Base font size')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::INTEGER, 
        "Base font size must be a numeric value.")
      ->setDefaultValue(0);
    $sub->addText('font_size_mapping', 'Font size key');
    $sub->addText('minimum_line_height', 'Minimum line height')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Minimum line height must be a numeric value.")
      ->setDefaultValue(number_format(120.0, 1));
    $sub->addText('line_height', 'Line height')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::INTEGER, 
        "Line height must be a numeric value.")
      ->setDefaultValue(0);
    $sub->addText('input_encoding', 'Input character encoding');
    $sub->addCheckbox('remove_paragraph_spacing', 'Remove spacing between paragraphs');
    $sub->addText('remove_paragraph_spacing_indent_size', 'Indent size')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Indent size must be a numeric value.")
      ->setDefaultValue(1.5);
    $sub->addCheckbox('insert_blank_line', 'Insert blank line between paragraphs');
    $sub->addText('insert_blank_line_size', 'Line size')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Line size must be a numeric value.")
      ->setDefaultValue(0.5);
    $sub->addSelect('change_justification', 'Text justification', array(
      'original' => 'Original',
      'left' => 'Left align',
      'justify' => 'Justify text'
    ));
    $sub->addCheckbox('smarten_punctuation', 'Smarten punctuation');
    $sub->addCheckbox('unsmarten_punctuation', 'UnSmarten punctuation');
    $sub->addCheckbox('asciiize', 'Transliterate unicode characters to ASCII');
    $sub->addCheckbox('keep_ligatures', 'Keep ligatures');
    $sub->addCheckbox('linearize_tables', 'Linearise tables');
    $sub->addTextArea('extra_css', 'Extra CSS');
    $sub->addCheckBox('filter_css_fonts', 'Fonts');
    $sub->addCheckBox('filter_css_margins', 'Margins');
    $sub->addCheckBox('filter_css_padding', 'Padding');
    $sub->addCheckBox('filter_css_floats', 'Floats');
    $sub->addCheckBox('filter_css_colors', 'Colors');
    $sub->addText('filter_css_other', 'Other CSS Properties');
    
    // Heuristic Processing
    $sub = $form->addContainer('heuristic');
    $sub->addCheckBox('enable_heuristics', 'Enable heuristic processing');
    $sub->addCheckBox('unwrap_lines', 'Unwrap lines')->setDefaultValue(true);
    $sub->addText('html_unwrap_factor', 'Line up-wrap factor')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Line up-wrap factor must be a numeric value.")
      ->setDefaultValue(number_format(0.4, 2));
    $sub->addCheckBox('markup_chapter_headings', 'Detect and markup unformatted chapter headings and sub headings')
      ->setDefaultValue(true);
    $sub->addCheckBox('renumber_headings', 'Renumber sequence of <h1> or <h2> tags to prevent splitting')
      ->setDefaultValue(true);
    $sub->addCheckBox('delete_blank_paragraphs', 'Delete blank lines between paragraphs')
      ->setDefaultValue(true);
    $sub->addCheckBox('format_scene_breaks', 'Ensure scene breaks are consistently formatted')
      ->setDefaultValue(true);
    $sub->addText('replace_scene_breaks', 'Replace soft scene breaks');
    $sub->addCheckBox('disable_dehyphenate', 'Remove unnecessary hyphens')
      ->setDefaultValue(true);
    $sub->addCheckBox('italicize_common_cases', 'Italicise common words and patterns')
      ->setDefaultValue(true);
    $sub->addCheckBox('fix_indents', 'Replace entity indents with CSS indents')
      ->setDefaultValue(true);
    
    // Page Setup
    $sub = $form->addContainer('setup');
    $sub->addSelect('output_profile', 'Output profile', array(
      'cybookg3' => 'Cybook G3', 
      'cybook_opus' => 'Cybook Opus', 
      'default' => 'Default Output Profile', 
      'generic_eink' => 'Generic e-ink', 
      'generic_eink_large' => 'Generic e-ink large', 
      'hanlinv3' => 'Hanlin V3', 
      'hanlinv5' => 'Hanlin V5', 
      'illiad' => 'Illiad', 
      'ipad' => 'iPad', 
      'irexdr1000' => 'IRex Digital Reader 1000', 
      'irexdr800' => 'IRex Digital Reader 800', 
      'jetbook5' => 'JetBook 5-inch', 
      'kindle' => 'Kindle', 
      'kindle_dx' => 'Kindle DX', 
      'kindle_fire' => 'Kindle Fire', 
      'kobo' => 'Kobo Reader', 
      'msreader' => 'Microsoft Reader', 
      'mobipocket' => 'Mobipocket Books', 
      'nook' => 'Nook', 
      'nook_color' => 'Nook Color', 
      'pocketbook_900' => 'PocketBook Pro 900', 
      'galaxy' => 'Samsung Galaxy', 
      'bambook' => 'Sanda Bambook', 
      'sony' => 'Sony Reader', 
      'sony300' => 'Sony Reader 300', 
      'sony900' => 'Sony Reader 900', 
      'sony-landscape' => 'Sony Reader Landscape', 
      'tablet' => 'Tablet'
    ))->setDefaultValue('generic_eink');
    $sub->addSelect('input_profile', 'Input profile', array(
      'cybookg3' => 'Cybook G3', 
      'cybook_opus' => 'Cybook Opus', 
      'default' => 'Default Input Profile', 
      'hanlinv3' => 'Hanlin V3', 
      'hanlinv5' => 'Hanlin V5', 
      'illiad' => 'Illiad', 
      'irexdr1000' => 'IRex Digital Reader 1000', 
      'irexdr800' => 'IRex Digital Reader 800', 
      'kindle' => 'Kindle', 
      'msreader' => 'Microsoft Reader', 
      'mobipocket' => 'Mobipocket Books', 
      'nook' => 'Nook', 
      'sony' => 'Sony Reader', 
      'sony300' => 'Sony Reader 300', 
      'sony900' => 'Sony Reader 900'
    ))->setDefaultValue('default');
    $sub->addText('margin_left', 'Left')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Margin must be a numeric value.")
      ->setDefaultValue(number_format(5.0, 1));
    $sub->addText('margin_top', 'Top')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Margin must be a numeric value.")
      ->setDefaultValue(number_format(5.0, 1));
    $sub->addText('margin_right', 'Right')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Margin must be a numeric value.")
      ->setDefaultValue(number_format(5.0, 1));
    $sub->addText('margin_bottom', 'Bottom')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Margin must be a numeric value.")
      ->setDefaultValue(number_format(5.0, 1));
    
    // Structure Detection
    $sub = $form->addContainer('structure');
    $sub->addText('chapter', 'Detect chapters at (XPath expression)');
    $sub->addSelect('chapter_mark', 'Chapter mark', array(
      'pagebreak' => 'pagebreak',
      'rule' => 'rule',
      'both' => 'both',
      'none' => 'none'
    ));
    $sub->addCheckbox('remove_first_image', 'Remove first image');
    $sub->addCheckbox('remove_fake_margins', 'Remove fake margins')
      ->setDefaultValue(true);
    $sub->addCheckbox('insert_metadata', 'Insert metadata as page at start of book');
    $sub->addText('page_breaks_before', 'Insert page breaks before (XPath expression)');
    
    // Table of Contents
    $sub = $form->addContainer('table');
    $sub->addCheckbox('use_auto_toc', 'Force use of auto-generated Table of Contents');
    $sub->addCheckbox('no_chapters_in_toc', 'Do not add detected chapters to the Table of Contents');
    $sub->addCheckbox('duplicate_links_in_toc', 'Allow duplicate links when creating the Table of Contents');
    $sub->addText('max_toc_links', 'Number of links to add to Table of Contents')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::INTEGER, 
        "Number of TOC links must be a numeric value.")
      ->setDefaultValue(50);
    $sub->addText('toc_threshold', 'Chapter threshold')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::INTEGER, 
        "Chapter threshold must be a numeric value.")
      ->setDefaultValue(6);
    $sub->addText('toc_filter', 'TOC Filter');
    $sub->addText('level1_toc', 'Level 1 TOC (XPath expression)');
    $sub->addText('level2_toc', 'Level 2 TOC (XPath expression)');
    $sub->addText('level3_toc', 'Level 3 TOC (XPath expression)');
    
    // Search & Replace
    $sub = $form->addContainer('search');
    $sub->addText('sr1_search', 'Search regular Expression');
    $sub->addText('sr1_replace', 'Replacement Text');
    $sub->addText('sr2_search', 'Search regular Expression');
    $sub->addText('sr2_replace', 'Replacement Text');
    $sub->addText('sr3_search', 'Search regular Expression');
    $sub->addText('sr3_replace', 'Replacement Text');
    
    // FB2 Input
    $sub = $form->addContainer('input_fb2');
    $sub->addCheckbox('no_inline_fb2_toc', 'Do not insert a Table of Contents at the beginning of the book');
    
    // PDF Input
    $sub = $form->addContainer('input_pdf');
    $sub->addText('unwrap_factor', 'Line Up-Wrapping Factor')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "PDF Input Line Up-Wrapping Factor must be a numeric value.")
      ->setDefaultValue(0.45);
    $sub->addCheckbox('no_images', 'No Images');
    
    // TXT (TXTZ) Input
    $sub = $form->addContainer('input_txt');
    $sub->addSelect('paragraph_type', 'Paragraph style', array(
      'auto' => 'auto',
      'block' => 'block',
      'single' => 'single',
      'print' => 'print',
      'unformatted' => 'unformatted',
      'off' => 'off'
    ));
    $sub->addSelect('formatting_type', 'Formatting style', array(
      'auto' => 'auto',
      'plain' => 'plain',
      'heuristic' => 'heuristic',
      'textile' => 'textile',
      'markdown' => 'markdown'
    ));
    $sub->addCheckbox('preserve_spaces', 'Preserve spaces');
    $sub->addCheckbox('txt_in_remove_indents', 'Remove indents at the beginning of lines');
    $sub->addCheckbox('markdown_disable_toc', 'Do not insert Table of Contents into output text when using markdown');
    
    // EPUB Output
    $sub = $form->addContainer('output_epub');
    $sub->addCheckbox('dont_split_on_page_breaks', 'Do not split on page breaks');
    $sub->addCheckbox('no_default_epub_cover', 'No default cover');
    $sub->addCheckbox('no_svg_cover', 'No SVG cover');
    $sub->addCheckbox('epub_flatten', 'Flatten EPUB file structure');
    $sub->addCheckbox('preserve_cover_aspect_ratio', 'Preserve cover aspect ratio');
    $sub->addText('flow_size', 'Split files larger than')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::INTEGER, 
        "EPUB Output Split files must be a numeric value.")
      ->setDefaultValue(260);
    
    // FB2 Output
    $sub = $form->addContainer('output_fb2');
    $sub->addSelect('sectionize', 'Sectionize', array(
      'toc' => 'toc',
      'files' => 'files',
      'nothing' => 'nothing'
    ))->setDefaultValue('files');
    $sub->addSelect('fb2_genre', 'Genre')
      ->setItems(array(
        'sf_history', 'sf_action', 'sf_epic', 'sf_heroic', 'sf_detective', 'sf_cyberpunk', 'sf_space', 'sf_social', 
        'sf_horror', 'sf_humor', 'sf_fantasy', 'sf', 'det_classic', 'det_police', 'det_action', 'det_irony', 
        'det_history', 'det_espionage', 'det_crime', 'det_political', 'det_maniac', 'det_hard', 'thriller', 
        'detective', 'prose_classic', 'prose_history', 'prose_contemporary', 'prose_counter', 'prose_rus_classic', 
        'prose_su_classics', 'love_contemporary', 'love_history', 'love_detective', 'love_short', 'love_erotica', 
        'adv_western', 'adv_history', 'adv_indian', 'adv_maritime', 'adv_geo', 'adv_animal', 'adventure', 
        'child_tale', 'child_verse', 'child_prose', 'child_sf', 'child_det', 'child_adv', 'child_education', 
        'children', 'poetry', 'dramaturgy', 'antique_ant', 'antique_european', 'antique_russian', 'antique_east', 
        'antique_myths', 'antique', 'sci_history', 'sci_psychology', 'sci_culture', 'sci_religion', 'sci_philosophy', 
        'sci_politics', 'sci_business', 'sci_juris', 'sci_linguistic', 'sci_medicine', 'sci_phys', 'sci_math', 
        'sci_chem', 'sci_biology', 'sci_tech', 'science', 'comp_www', 'comp_programming', 'comp_hard', 'comp_soft', 
        'comp_db', 'comp_osnet', 'computers', 'ref_encyc', 'ref_dict', 'ref_ref', 'ref_guide', 'reference', 
        'nonf_biography', 'nonf_publicism', 'nonf_criticism', 'design', 'nonfiction', 'religion_rel', 
        'religion_esoterics', 'religion_self', 'religion', 'humor_anecdote', 'humor_prose', 'humor_verse', 'humor', 
        'home_cooking', 'home_pets', 'home_crafts', 'home_entertain', 'home_health', 'home_garden', 'home_diy', 
        'home_sport', 'home_sex', 'home'
      ), false)
      ->setDefaultValue('antique');
    
    // HTMLZ Output
    $sub = $form->addContainer('output_htmlz');
    $sub->addSelect('htmlz_css_type', 'How to handle CSS', array(
      'class' => 'class',
      'inline' => 'inline',
      'tag' => 'tag'
    ));
    $sub->addSelect('htmlz_class_style', 'How to handle class based CSS', array(
      'external' => 'external',
      'inline' => 'inline'
    ));
    
    // MOBI Output
    $sub = $form->addContainer('output_mobi');
    $sub->addCheckbox('no_inline_toc', 'Do not add Table of Contents to book');
    $sub->addText('toc_title', 'Title for Table of Contents');
    $sub->addCheckbox('mobi_toc_at_start', 'Put generated Table of Contents at start of book instead of end');
    $sub->addCheckbox('mobi_ignore_margins', 'Ignore margins');
    $sub->addCheckbox('prefer_author_sort', 'Use author sort for author');
    $sub->addCheckbox('dont_compress', 'Disable compression of the file contents');
    $sub->addText('personal_doc', 'Personal Doc tag')
      ->setDefaultValue('[PDOC]');
    $sub->addCheckbox('share_not_sync', 'Enable sharing of book content via Facebook, etc. WARNING: Disables last read syncing');
    
    // PDB Output
    $sub = $form->addContainer('output_pdb');
    $sub->addSelect('format', 'Format', array(
      'doc' => 'doc',
      'ztxt' => 'ztxt',
      'ereader' => 'ereader'
    ));
    $sub->addText('pdb_output_encoding', 'Output Encoding')
      ->setDefaultValue('cp1252');
    $sub->addCheckbox('inline_toc', 'Inline TOC');
    
    // PDF Output
    $sub = $form->addContainer('output_pdf');
    $sub->addSelect('paper_size', 'Paper Size')
      ->setItems(array(
        'b2', 'a9', 'executive', 'tabloid', 'b4', 'b5', 'b6', 'b7', 'b0', 'b1', 'letter', 'b3', 
        'a7', 'a8', 'b8', 'b9', 'a3', 'a1', 'folio', 'c5e', 'dle', 'a0', 'ledger', 'legal', 
        'a6', 'a2', 'b10', 'a5', 'comm10e', 'a4'
      ), false)
      ->setDefaultValue('letter');
    $sub->addSelect('orientation', 'Orientation', array(
      'portrait' => 'portrait',
      'landscape' => 'landscape',
    ));
    $sub->addText('custom_size', 'Custom size');
    $sub->addCheckbox('preserve_cover_aspect_ratio', 'Preserve aspect ratio of cover');
    
    // PMLZ Output
    $sub = $form->addContainer('output_pmlz');
    $sub->addText('pml_output_encoding', 'Output Encoding')
      ->setDefaultValue('cp1252');
    $sub->addCheckbox('inline_toc', 'Inline TOC');
    $sub->addCheckbox('full_image_depth', 'Do not reduce image size and depth');
    
    // RB Output
    $sub = $form->addContainer('output_rb');
    $sub->addCheckbox('inline_toc', 'Inline TOC');
    
    // SNB Output
    $sub = $form->addContainer('output_snb');
    $sub->addCheckbox('snb_insert_empty_line', 'Insert empty line between paragraphs');
    $sub->addCheckbox('snb_dont_indent_first_line', 'Don\'t indent the first line for each paragraph');
    $sub->addCheckbox('snb_hide_chapter_name', 'Hide chapter name');
    $sub->addCheckbox('snb_full_screen', 'Optimize for full-screen view');
    
    // TXT (TXTZ) Output
    $sub = $form->addContainer('output_txt');
    $sub->addText('txt_output_encoding', 'Output Encoding')
      ->setDefaultValue('utf-8');
    $sub->addSelect('newline', 'Line ending style', array(
      'windows' => 'windows',
      'unix' => 'unix',
      'old_mac' => 'old_mac',
      'system' => 'system'
    ))->setDefaultValue('system');
    $sub->addSelect('txt_output_formatting', 'Formatting', array(
      'plain' => 'plain',
      'markdown' => 'markdown',
      'textile' => 'textile'
    ));
    $sub->addCheckbox('inline_toc', 'Inline TOC');
    $sub->addText('max_line_length', 'Maximum line length')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::INTEGER, 
        "TXT Output Maximum line length must be a numeric value.")
      ->setDefaultValue(0);
    $sub->addCheckbox('force_max_line_length', 'Force maximum line length');
    $sub->addCheckbox('keep_links', 'Do not remove links (<a> tags) before processing');
    $sub->addCheckbox('keep_image_references', 'Do not remove image references before processing');
    $sub->addCheckbox('keep_color', 'Keep text color, when possible');
    
    $form->addSubmit('send', 'Convert');
    
    $form->onSuccess[] = callback($this, 'convertFormSubmitted');
    return $form;
  }
  
  /**
   * Handle submitted convert form
   * @param Nette\Application\UI\Form $form 
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function convertFormSubmitted($form)
  {
    $values = $form->getValues();
    
    $id = $this->getParam('id');
    if (!$this->calibre->checkConvertable($id))
      throw new NA\BadRequestException('Can\'t convert.');
    
    if ($this->calibre->convert($values, $id)) {
      $msg = $this->context->translator->translate(
        "Book has been successfully converted.");
      $this->flashMessage($msg, 'ok');
      $this->redirect('this');
    }
    else {
      $msg = $this->context->translator->translate(
        "Error: Weblibre was unable convert the book!");
      $this->flashMessage($msg, 'error');
    }
  }
  
  /**
   * Convert section
   * @param int $id
   * @return void Book id
   * @throws Nette\Application\BadRequestException
   */
  public function actionDefault($id)
  {
    if (!$this->calibre->checkConvertable($id))
      throw new NA\BadRequestException('Can\'t convert.');
    
    $this->bookName = $this->calibre->getBookName($id);
    
    $this->metadata = $this->calibreEdit->getMetadata($id);
  }
  
  /**
   * Render convert section
   * @param int $id Book id
   * @param string $format Format name
   * @return void
   */
  public function renderDefault($id, $format = NULL)
  {
    // Add navigation
    $this->addNavigation($this->bookName, 'Book:', false, $id);
    $this->addNavigation('Convert', NULL);
    
    // Info into template
    $this->template->bookName = $this->bookName;
    
    // Default form values
    $this['convertForm']['input_format']->setDefaultValue($format);
    
    $this['convertForm']['metadata']['title']
      ->setDefaultValue($this->metadata['title']);
    $this['convertForm']['metadata']['authors']
      ->setDefaultValue($this->metadata['authors']);
    $this['convertForm']['metadata']['author_sort']
      ->setDefaultValue($this->metadata['author_sort']);
    $this['convertForm']['metadata']['publisher']
      ->setDefaultValue($this->metadata['publisher']);
    $this['convertForm']['metadata']['tags']
      ->setDefaultValue($this->metadata['tags']);
    $this['convertForm']['metadata']['series']
      ->setDefaultValue($this->metadata['series']);
    $this['convertForm']['metadata']['series_index']
      ->setDefaultValue($this->metadata['series_index']);
    $this['convertForm']['metadata']['comments']
      ->setDefaultValue($this->metadata['comments']);
  }
  
}