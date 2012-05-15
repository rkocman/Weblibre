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

// Enable usage of Xvfb on Unix system
// Xvfb package is required
// This is essential for various formats conversion
$wconfig['useXvfb'] = true;



// Weblibre debug mode
$wconfig['debug'] = false;



// Define caching
$wconfig['caheResults'] = true;



// Define prefered language
//$wconfig['preferedLang'] = "en";
$wconfig['preferedLang'] = "cs";



// Define path to Calibre main folder
// Warning: Relative path "../" refers to folder with this config file
$wconfig['calibre'] = "../calibre/0.8.34/";



// Define users
// login    - user login
// password - user password
// database - path to Calibre library
// Warning: Relative path "../" refers to folder with this config file
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