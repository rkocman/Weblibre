<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application\UI;

/**
 * Browse library presenter
 *
 * @author     Radim Kocman
 */
final class BrowsePresenter extends SignedPresenter 
{
  /** 
   * Sort by selected
   * @var string
   * @persistent
   */
  public $sortBy = 'Title';
  
  /**
   * @var string
   * @persistent
   */
  public $search;
  
  /** 
   * Current page in paging
   * @var int
   * @persistent
   */
  public $page = 1;
  
  /** @var int */
  private $pageMax;
  
  /**
   * Sort by values
   * @var array
   */
  protected $sortByArray = array(
    'Author Sort',
    'Authors',
    'Date',
    'Identifiers',
    'Modified',
    'Published',
    'Publishers',
    'Ratings',
    'Series',
    'Size',
    'Tags',
    'Title'
  );
  
  /** 
   * Number of records on one page
   * @var int
   */
  private $records = 10;
  
  /** 
   * Array with current result
   * @var array 
   */
  private $result;
  
  
  /** @var BrowseCalibre */
  private $calibreModel = NULL;
  
  /**
   * Connect Calibre model
   * @return BrowseCalibre
   */
  public function getCalibre() 
  {
    if (!isset($this->calibreModel)) {
      $data = $this->user->getIdentity()->getData();
      $this->calibreModel = new BrowseCalibre($data['db']);
    }
    
    return $this->calibreModel;
  }
  
  
  
  /**
   * Check values after start
   * @return void
   */
  protected function startup() 
  {
    parent::startup();
    
    // Check sortBy value
    if (!in_array($this->sortBy, $this->sortByArray))
      $this->sortBy = 'Title';
    
    // Clean persistent parameters
    if ($this->view != 'allBooks')
      $this->search = '';
    
    // Number of records by page
    if ($this->mode == 'Classic')
      $this->records = 40;
    
    // Check page
    if (!is_numeric($this->page) || $this->page < 1)
      $this->page = 1;
    if ($this->page != round($this->page))
      $this->page = round($this->page);
  }
  
  /**
   * Redirect default section
   * $return void
   */
  public function actionDefault() 
  {
    $this->redirect('Browse:newest');
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
    
    // Add selection
    $this->template->selection = array(
      array(
        'title' => 'Newest',
        'href'  => 'Browse:newest',
      ),
      array(
        'title' => 'All books',
        'href'  => 'Browse:allBooks',
      ),
      array(
        'title' => 'Authors',
        'href'  => 'Browse:authors',
      ),
      array(
        'title' => 'Languages',
        'href'  => 'Browse:languages',
      ),
      array(
        'title' => 'Publishers',
        'href'  => 'Browse:publishers',
      ),
      array(
        'title' => 'Ratings',
        'href'  => 'Browse:ratings',
      ),
      array(
        'title' => 'Series',
        'href'  => 'Browse:series',
      ),
      array(
        'title' => 'Tags',
        'href'  => 'Browse:tags',
      ),
    );
    
    // Set browse layout
    $this->setLayout('browse');
    
    // Handle Ajax
    if($this->isAjax())
      $this->invalidateControl('result');
    
    // Add variable
    $this->template->page = $this->page;
    $this->template->pageMax = $this->pageMax;
    $this->template->result = $this->result;
    
    if ($this->isAjax()) {
      
      // First record
      $firstRecord = (($this->page-1) * $this->records) + 1;
      if ($firstRecord > $this->result['count']) $firstRecord = 0;
      $this->template->firstRecord = $firstRecord;
      
      // Last record
      $lastRecord = $this->page * $this->records;
      if ($lastRecord > $this->result['count']) $lastRecord = $this->result['count'];
      $this->template->lastRecord = $lastRecord;
      
    }
  }
  
  /**
   * Sort sortBy
   * @var array $a First title
   * @var array $b Second title
   * @return int
   */
  private function sortSortBy($a, $b) 
  {
    return strnatcmp($a['title'], $b['title']);
  }
  
  /**
	 * Sort by form
	 * @return Nette\Application\UI\Form
	 */
  protected function createComponentSortByForm($name) 
  {  
    $form = new UI\Form;
    
    foreach($this->sortByArray as $data) {
      $sort[$data] = array( 
        'id' => $data,
        'title' => $this->context->translator->translate($data)
      );
    }
    usort($sort, array($this, 'sortSortBy'));
    foreach($sort as $data) {
      $sort2[$data['id']] = $data['title'];
    }
    
    $form->addSelect('sortBy')
      ->setItems($sort2)
      ->setDefaultValue($this->sortBy);
    
    $form->onSuccess[] = callback($this, 'sortByFormSubmitted');
    return $form;
  }
  
  /**
   * Handle change of sorting
   * @param Nette\Application\UI\Form $form
   * @return void
   */
  public function sortByFormSubmitted($form) 
  {
    $values = $form->getValues();
    $this->sortBy = $values['sortBy'];
    $this->redirect('this');
  }
  
  /**
	 * Search form
	 * @return Nette\Application\UI\Form
	 */
  protected function createComponentSearchForm($name) 
  {  
    $form = new UI\Form;
    $form->setTranslator($this->context->translator);
    
    $form->addText('search')->setValue($this->search);
    $form->addSubmit('send', 'Search');
    
    $form->onSuccess[] = callback($this, 'searchFormSubmitted');
    return $form;
  }
  
  /**
   * Handle search
   * @param Nette\Application\UI\Form $form
   * @return void
   */
  public function searchFormSubmitted($form) 
  {
    $values = $form->getValues();
    $this->search = $values['search'];
    $this->redirect('Browse:allBooks');
  }
  
  /**
   * Check correct page number
   * @return void
   */
  private function checkPage() 
  {
    $count = $this->result['count'];
    $this->pageMax = ($count == 0)? 1 : ceil($count/$this->records);
    if ($this->page > $this->pageMax && $this->page != 1) {
      $this->page = $this->pageMax;
      $this->redirect('this');
    }
  }
  
  
  
  /**
   * Action newest books
   * @return void
   */
  public function actionNewest() 
  {
    if ($this->isAjax()) {
      $this->result = $this->calibre->getNewestBooks($this->page, $this->records);
      $this->checkPage();
    }
  }
  
  /**
   * Render newest books
   * @return void
   */
  public function renderNewest() 
  {
    // Add navigation
    $this->addNavigation('Newest', '');
  }
  
  
  
  /**
   * Action all books
   * @return void
   */
  public function actionAllBooks() 
  {
    if ($this->isAjax()) {
      $this->result = $this->calibre->getAllBooks(
        $this->page, $this->records, $this->sortBy, $this->search);
      $this->checkPage();
    }
  }
  
  /**
   * Render all books
   * @return void
   */
  public function renderAllBooks() 
  {
    // Add navigation
    $this->addNavigation('All books', '');
  }
  
  
  
  /**
   * Action authors
   * @param int|NULL $id Author id
   * @return void
   */
  public function actionAuthors($id = NULL) 
  {
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->result = $this->calibre->getAuthors();
      else
        $this->result = $this->calibre->getAuthorName($id);
    }
    else {
      $this->result = $this->calibre->getAuthorBooks(
        $this->page, $this->records, $this->sortBy, $id);
      $this->checkPage();
    }
  }
  
  /**
   * Render books by authors
   * @param int|NULL $id Author id
   * @return void
   */
  public function renderAuthors($id = NULL) 
  {
    // Add navigation
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->addNavigation('Authors', '');
      else {
        $this->addNavigation('Authors', 'Browse:authors');
        $this->addNavigation($this->result, '', false);
      }
    }
    
    $this->template->id = $id;
  }
  
  
  
  /**
   * Action languages
   * @param int|NULL $id Language id
   * @return void
   */
  public function actionLanguages($id = NULL) 
  {
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->result = $this->calibre->getLanguages();
      else
        $this->result = $this->calibre->getLanguageName($id);
    }
    else {
      $this->result = $this->calibre->getLanguageBooks(
        $this->page, $this->records, $this->sortBy, $id);
      $this->checkPage();
    }
  }
  
  /**
   * Render books by languages
   * @param int|NULL $id Language id
   * @return void
   */
  public function renderLanguages($id = NULL) 
  {
    // Add navigation
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->addNavigation('Languages', '');
      else {
        $this->addNavigation('Languages', 'Browse:languages');
        $this->addNavigation($this->result, '', false);
      }
    }
    
    $this->template->id = $id;
  }
  
  
  
  /**
   * Action publishers
   * @param int|NULL $id Publisher id
   * @return void
   */
  public function actionPublishers($id = NULL) 
  {
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->result = $this->calibre->getPublishers();
      else
        $this->result = $this->calibre->getPublisherName($id);
    }
    else {
      $this->result = $this->calibre->getPublisherBooks(
        $this->page, $this->records, $this->sortBy, $id);
      $this->checkPage();
    }
  }
  
  /**
   * Render books by publishers
   * @param int|NULL $id Publisher id
   * @return void
   */
  public function renderPublishers($id = NULL) 
  {
    // Add navigation
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->addNavigation('Publishers', '');
      else {
        $this->addNavigation('Publishers', 'Browse:publishers');
        $this->addNavigation($this->result, '', false);
      }
    }
    
    $this->template->id = $id;
  }
  
  
  
  /**
   * Action ratings
   * @param int|NULL $id Rating id
   * @return void
   */
  public function actionRatings($id = NULL) 
  {
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->result = $this->calibre->getRatings();
      else
        $this->result = $this->calibre->getRatingName($id);
    }
    else {
      $this->result = $this->calibre->getRatingBooks(
        $this->page, $this->records, $this->sortBy, $id);
      $this->checkPage();
    }
  }
  
  /**
   * Render books by rating
   * @param int|NULL $id Rating id
   * @return void
   */
  public function renderRatings($id = NULL) 
  {
    // Add navigation
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->addNavigation('Ratings', '');
      else {
        $this->addNavigation('Ratings', 'Browse:ratings');
        $this->addNavigation(($this->result/2), '', false);
      }
    }
    
    $this->template->id = $id;
  }
  
  
  
  /**
   * Action series
   * @param int|NULL $id Series id
   * @return void
   */
  public function actionSeries($id = NULL) 
  {
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->result = $this->calibre->getSeries();
      else
        $this->result = $this->calibre->getSeriesName($id);
    }
    else {
      $this->result = $this->calibre->getSeriesBooks(
        $this->page, $this->records, $this->sortBy, $id);
      $this->checkPage();
    }
  }
  
  /**
   * Render books by series
   * @param int|NULL $id Series id
   * @return void
   */
  public function renderSeries($id = NULL) 
  {
    // Add navigation
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->addNavigation('Series', '');
      else {
        $this->addNavigation('Series', 'Browse:series');
        $this->addNavigation($this->result, '', false);
      }
    }
    
    $this->template->id = $id;
  }
  
  
  
  /**
   * Action tags
   * @param int|NULL $id Tag id
   * @return void
   */
  public function actionTags($id = NULL) 
  {
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->result = $this->calibre->getTags();
      else
        $this->result = $this->calibre->getTagName($id);
    }
    else {
      $this->result = $this->calibre->getTagBooks(
        $this->page, $this->records, $this->sortBy, $id);
      $this->checkPage();
    }
  }
  
  /**
   * Render books by tags
   * @param int|NULL $id Tag id
   * @return void
   */
  public function renderTags($id = NULL) 
  {
    // Add navigation
    if (!$this->isAjax()) {
      if ($id === NULL)
        $this->addNavigation('Tags', '');
      else {
        $this->addNavigation('Tags', 'Browse:tags');
        $this->addNavigation($this->result, '', false);
      }
    }
    
    $this->template->id = $id;
  }

}
