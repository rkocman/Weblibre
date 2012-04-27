<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application\UI,
	Nette\Security as NS;

/**
 * Sign in/out presenter
 *
 * @author  Radim Kocman
 */
final class SignPresenter extends BasePresenter 
{
  
  /**
   * Check if user isn't signed
   * @return void
   */
  public function actionDefault() 
  {
    if ($this->user->isLoggedIn())
      $this->redirect('Browse:');
  }
  
	/**
	 * Sign in form component factory
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new UI\Form;
    $form->setTranslator($this->context->translator);
    
		$form->addText('login', 'Login:')
			->setRequired('Please provide a login.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please provide a password.');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = callback($this, 'signInFormSubmitted');
		return $form;
	}

  /**
   * Handle submitted sign in form
   * @param Nette\Application\UI\Form $form 
   * @return void
   */
	public function signInFormSubmitted($form)
	{
    $msg = $this->context->translator->translate("Wrong login or password!");
		try {
			$values = $form->getValues();
			$this->getUser()->setExpiration(0, TRUE);
			$this->getUser()->login($values->login, $values->password);
      $this->redirect('this');
		} catch (NS\AuthenticationException $e) {
			$this->flashMessage($msg, 'error');
		}
	}
  
  /**
   * Sort languages
   * @var array $a First language
   * @var array $b Second language
   * @return int
   */
  private function sortLang($a, $b) 
  {
    return strnatcmp($a['lang'], $b['lang']);
  }
  
  /**
	 * Language selection form component factory
	 * @return Nette\Application\UI\Form
	 */
  protected function createComponentSelectLanguageForm($name) 
  {
    $form = new UI\Form;
    
    foreach($GLOBALS['wlang'] as $key => $data) {
      $lang[$key] = array( 
        'short' => $key,
        'lang' => $data['lang']
      );
    }
    usort($lang, array($this, 'sortLang'));
    foreach($lang as $data) {
      $lang2[$data['short']] = $data['lang'];
    }

    $form->addSelect('language')
      ->setItems($lang2)
      ->setDefaultValue($this->lang);
    
    $form->onSuccess[] = callback($this, 'selectLanguageFormSubmitted');
    return $form;
  }
  
  /**
   * Handle submitted select language form
   * @param Nette\Application\UI\Form $form 
   * @return void
   */
  public function selectLanguageFormSubmitted($form) 
  {
    $values = $form->getValues();
    $this->redirect('this', array("lang" => $values['language']));
  }

  /**
   * Sign out user
   * @return void
   */
	public function actionOut()
	{
		$this->getUser()->logout();
		$this->redirect('default');
	}

}
