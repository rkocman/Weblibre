<?php

/**
 * This file is part of the Weblibre
 *
 * Copyright (c) 2012 Radim Kocman (xkocma03)
 * @author  Radim Kocman 
 */

use Nette\Application\Routers\Route;


// Load Weblibre config files
require_once '../config.php';
require_once '../lang/lang.php';


// Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';


// Configure application
$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
//$configurator->setProductionMode($configurator::AUTO);
$configurator->setProductionMode(!$wconfig['debug']);
$configurator->enableDebugger(__DIR__ . '/../log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();

// Configure debuger
Nette\Diagnostics\Debugger::$maxDepth = 5;
Nette\Diagnostics\Debugger::$maxLen = 500;

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/config/config.neon');
$container = $configurator->createContainer();

// Error Presenter
$container->application->errorPresenter = 'Error';

// Date Picker
\Nette\Forms\Container::extensionMethod('addDatePicker', function (\Nette\Forms\Container $container, $name, $label = NULL) {
    return $container[$name] = new \JanTvrdik\Components\DatePicker($label);
});

// Setup router
$container->router[] = new Route('index.php', 
        'Browse:default', Route::ONE_WAY);
$container->router[] = new Route('', array(
        'lang' => $wconfig['preferedLang'], 'presenter' => 'Browse'), Route::ONE_WAY);
$container->router[] = new Route('<lang>/', array(
        'presenter' => 'Browse'), Route::ONE_WAY);

$container->router[] = new Route('<lang>/sign-in/', array(
        'presenter' => 'Sign', 'action' => 'default'));
$container->router[] = new Route('<lang>/sign-out/', array(
        'presenter' => 'Sign', 'action' => 'out'));
$container->router[] = new Route('<lang>/cover/<id>/<size>/', array(
        'presenter' => 'Cover', 'action' => 'default'));

$container->router[] = new Route('<lang>/<presenter>/[<action=default>/][<id>/]'
        );

// Configure and run the application!
$container->application->run();
