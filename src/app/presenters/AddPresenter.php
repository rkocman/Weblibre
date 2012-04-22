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
 * Add new books presenter
 *
 * @author     Radim Kocman
 */
final class AddPresenter extends SignedPresenter 
{
  
  /** @var string **/
  private $bookName;
  
  

  /** @var BrowseCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return AddCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new AddCalibre($data['db']);
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
    
    // Set add layout
    $this->setLayout('add');
  }
  
  
  
  /**
   * Add form
   * @return Nette\Application\UI\Form
   */
  protected function createComponentAddForm() 
  {
    $form = new UI\Form;
    $form->setTranslator($this->context->translator);
    
    for($i = 1; $i <= 5; $i++) {
      $form->addGroup();
      $sub = $form->addContainer($i);
      $sub->addUpload('book');
    }
    
    $form->setCurrentGroup(NULL);
    $form->addSubmit('send', 'Add to library');
    
    $form->onValidate[] = callback($this, 'validateAddForm');
    $form->onSuccess[] = callback($this, 'addFormSubmitted');
    return $form;
  }
  
  /**
   * Validate add form
   * @param Nette\Application\UI\Form $form 
   * @return void
   */
  public function validateAddForm($form) 
  {
    $values = $form->getValues();
    
    foreach($values as $key => $value) {
      if ($value['book']->getError() != 4)
        return;
    }
    
    $form->addError('No file uploaded.');
  }
  
  /**
   * Handle submitted add form
   * @param Nette\Application\UI\Form $form 
   * @return void
   */
  public function addFormSubmitted($form) 
  {
    $values = $form->getValues();
    
    if ($this->calibre->addBooks($values)) {
      $msg = $this->context->translator->translate(
        "Books have been successfully added to your library.");
      $this->flashMessage($msg, 'ok');
      $this->redirect('this');
    }
    else {
      $msg = $this->context->translator->translate(
        "Error: Weblibre was unable to add some books into the library!");
      $this->flashMessage($msg, 'error');
    }
  }
  
  /**
   * Render Add new books
   * @return void
   */
  public function renderDefault() 
  {
    // Add navigation
    $this->addNavigation('Add new books', '');
  }

  
  
  /**
   * Add empty book
   * @return void
   */
  public function handleAddEmpty() 
  {
    if ($this->calibre->addEmptyBook()) {
      $msg = $this->context->translator->translate(
        "Empty book has been successfully added to your library.");
      $this->flashMessage($msg, 'ok');
    }
    else {
      $msg = $this->context->translator->translate(
        "Error: Weblibre was unable to add empty book into the library!");
      $this->flashMessage($msg, 'error');
    }
    $this->redirect('this');
  }
  
  
  
  /**
   * Add format form
   * @return Nette\Application\UI\Form
   */
  protected function createComponentAddFormatForm() 
  {
    $form = new UI\Form;
    $form->setTranslator($this->context->translator);
    
    $form->addGroup();
    $sub = $form->addContainer(1);
    $sub->addUpload('book')
      ->setRequired('File is reguired.');
    
    $form->setCurrentGroup(NULL);
    $form->addSubmit('send', 'Add to library');
    
    $form->onSuccess[] = callback($this, 'addFormatFormSubmitted');
    return $form;
  }
  
  /**
   * Handle submitted add format form
   * @param Nette\Application\UI\Form $form 
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function addFormatFormSubmitted($form) 
  {
    $values = $form->getValues();
    
    $id = $this->getParam('id');
    if (!$this->calibre->checkBook($id))
      throw new NA\BadRequestException('No such book.');
    
    if ($this->calibre->addFormat($values, $id)) {
      $msg = $this->context->translator->translate(
        "Format has been successfully added to your library.");
      $this->flashMessage($msg, 'ok');
      $this->redirect('this');
    }
    else {
      $msg = $this->context->translator->translate(
        "Error: Weblibre was unable to add format into the library!");
      $this->flashMessage($msg, 'error');
    }
  }
  
  /**
   * Section Add format
   * @param int $id Book id
   * @return void
   * @throws Nette\Application\BadRequestException
   */
  public function actionFormat($id) 
  {
    if (!$this->calibre->checkBook($id))
      throw new NA\BadRequestException('No such book.');
    
    $this->bookName = $this->calibre->getBookName($id);
  }
  
  /**
   * Render Add format
   * @param int $id Book id
   * @return void
   */
  public function renderFormat($id) 
  {
    // Add navigation
    $this->addNavigation('Library', 'Browse:');
    $this->addNavigation($this->bookName, 'Book:', false, $id);
    $this->addNavigation('Add format', NULL);
    
    // Book name into template
    $this->template->bookName = $this->bookName;
  }
  
}
