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
   * @return void
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
   * @param int $id
   * @param string $format
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