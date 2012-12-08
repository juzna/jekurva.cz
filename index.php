<?php

use Nette\Application\Routers\Route;

// Load libraries
require __DIR__ . '/app/libs/nette.min.php';


$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
$configurator->enableDebugger(__DIR__ . '/app/log');

$configurator->setTempDirectory(__DIR__ . '/app/temp');

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/app/config.neon', FALSE);
$configurator->addConfig(__DIR__ . '/app/config.local.neon', FALSE);
$container = $configurator->createContainer();

// Setup routes
$container->router[] = new Route('/', function(\NetteModule\MicroPresenter $presenter) use ($container) {
	$db = $container->nette->database->default;

	$time = @$_GET['t'] ?: date('H:i:s');
	$foto = $db->table('foto')->where('od <= ? AND do >= ?', $time, $time)->order('RAND()')->fetch();

	$tpl = $presenter->createTemplate()->setFile(__DIR__ . '/app/foto.latte');
	$tpl->foto = $foto;

	return $tpl;
});


// Run the application!
$container->application->run();
