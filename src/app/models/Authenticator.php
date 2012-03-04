<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Security as NS;


/**
 * Users authenticator
 *
 * @author  Radim Kocman
 */
final class Authenticator extends Nette\Object implements NS\IAuthenticator
{
	/** @var array */
	private $users;

  /**
   * Load users
   */
	public function __construct()
	{
		$this->users = $GLOBALS['wconfig']['users'];
	}

	/**
	 * Performs an authentication
	 * @param  array $credentials
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($login, $password) = $credentials;
    
    foreach($this->users as $id => $user) {
      if ($user['login'] === $login && $user['password'] === $password)
        return new NS\Identity($id, NULL, array(
          'login' => $user['login'],
          'db' => $user['database'],
        ));
    }
    throw new NS\AuthenticationException(NULL, self::IDENTITY_NOT_FOUND);
	}

}
