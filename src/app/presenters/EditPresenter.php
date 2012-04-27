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
 * Edit presenter
 *
 * @author     Radim Kocman
 */
final class EditPresenter extends SignedPresenter 
{
  /** @var string **/
  private $bookName;
  
  /** @var array **/
  private $metadata;
  
  
  
  /** @var EditCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return EditCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new EditCalibre($data['db']);
    }
    
    return $this->calibreModel;
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
    $this->setLayout('edit');
  }
  
  
  
  /**
   * Edit form
   * @return Nette\Application\UI\Form
   */
  protected function createComponentEditForm()
  {
    $form = new UI\Form;
    $form->setTranslator($this->context->translator);
    
    $form->addText('title', 'Title');
    $form->addText('title_sort', 'Title sort');
    $form->addText('authors', 'Author(s)');
    $form->addText('author_sort', 'Author sort');
    $form->addText('series', 'Series');
    $form->addText('series_index', 'Number')
      ->setType('number')
      ->addRule(\Nette\Forms\Form::FLOAT, 
        "Series index must be a numeric value.");
    $form->addSelect('rating', 'Rating', array(
      0  => "0 stars",
      2  => "1 stars",
      4  => "2 stars",
      6  => "3 stars",
      8  => "4 stars",
      10 => "5 stars"
    ));
    $form->addText('tags', 'Tags');
    $form->addText('identifiers', 'IDs');
    $form->addDatePicker('date', 'Date')
      ->setAttribute('class', 'longRange');
    $form->addDatePicker('published', 'Published')
      ->setAttribute('class', 'longRange');
    $form->addText('publisher', 'Publisher');
    $form->addText('languages', 'Languages');
    $form->addTextArea('comments', 'Comments');
    
    $form->addSubmit('send', 'Save metadata');
    
    $form->onSuccess[] = callback($this, 'editFormSubmitted');
    return $form;
  }
  
  /**
   * Handle submitted edit form
   * @param Nette\Application\UI\Form $form 
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function editFormSubmitted($form)
  {
    $values = $form->getValues();
    
    $id = $this->getParam('id');
    if (!$this->calibre->checkBook($id))
      throw new NA\BadRequestException('No such book.');
    
    if ($this->calibre->saveMetadata($values, $id)) {
      $msg = $this->context->translator->translate(
        "Metadata has been successfully saved.");
      $this->flashMessage($msg, 'ok');
      $this->redirect('Book:', $id);
    }
    else {
      $msg = $this->context->translator->translate(
        "Error: Weblibre was unable to save metadata!");
      $this->flashMessage($msg, 'error');
    }
  }
  
  /**
   * Metadata editing
   * @param int $id Book id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionDefault($id)
  {
    if (!$this->calibre->checkBook($id))
      throw new NA\BadRequestException('No such book.');
    
    $this->bookName = $this->calibre->getBookName($id);
    
    $this->metadata = $this->calibre->getMetadata($id);
  }
  
  /**
   * Render metadata editing
   * @param int $id Book id
   * @return void
   */
  public function renderDefault($id)
  {
    // Add navigation
    $this->addNavigation($this->bookName, 'Book:', false, $id);
    $this->addNavigation('Edit metadata', NULL);
    
    // Book name into template
    $this->template->bookName = $this->bookName;
    
    // Default form values
    $this['editForm']->setDefaults($this->metadata);
  }
  
}