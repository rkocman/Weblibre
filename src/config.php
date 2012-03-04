<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

/*
 * Weblibre configuration file
 */

global $wconfig;


// Define environment
$wconfig['env'] = "windows";
//$wconfig['env'] = "unix";



// Define caching
$wconfig['caheResults'] = true;



// Define path to Calibre main folder
$wconfig['calibre'] = "../calibre/0.8.34/";



// Define users
// login    - user login
// password - user password
// database - path to Calibre library
$wconfig['users'] = array(

  array( // User
    "login"     => "test",
    "password"  => "test",
    "database"  => "../database/sample/",
  ),
  
  array( // User
    "login"     => "test2",
    "password"  => "test2",
    "database"  => "../database/sample/",
  ),
  
);