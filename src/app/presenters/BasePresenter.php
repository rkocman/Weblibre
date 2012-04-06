<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

/**
 * Base class for all application presenters
 *
 * @author  Radim Kocman
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter 
{  
  /** 
   * @var string
   * @persistent
   */
  public $lang;
  
  
  /**
   * Startup with language setting
   * @return void
   */
  protected function startup() 
  {
    parent::startup();
    
    $languages = $GLOBALS["wlang"];
    
    if (!isset($this->lang) or !isset($languages[$this->lang])) {
      $this->lang = "en";
    }
    
    $this->context->translator->setLang($this->lang);
  }
  
  /**
   * Add translator into template
   * @return Nette\Templating\ITemplate
   */
  protected function createTemplate($class = NULL)
  {
    $template = parent::createTemplate($class);

    $template->setTranslator($this->context->translator);

    return $template;
  }
  
}
