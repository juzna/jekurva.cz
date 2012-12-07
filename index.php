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
$container->router[] = new Route('//[!<domain>]/', function(\NetteModule\MicroPresenter $presenter, $domain, $do = NULL) use ($container) {
	$db = $container->nette->database->default;

	// Parse domain
	if ( ! preg_match('/^(?:(.+)\.)?([-\w]+\.\w+)$/', $domain, $match)) throw new \Nette\Application\BadRequestException('Wrong domain name');
	list (, $name, $domain) = $match;

	$reservedNames = array(
		'www',
	);
	if (in_array($name, $reservedNames)) $name = '';

	// Kdo?
	if ( ! $site = $db->table('site')->where('domain', $domain)->fetch()) throw new \Nette\Application\BadRequestException('Site unknown');
	$who = $db->table('who')->where(array('site' => $site->id, 'name' => $name))->fetch();

	// Signal
	switch ($do) {
		case 'save':
			if ($who) die("Uz existuje, sorry");
			$db->table('who')->insert(array(
				'site' => $site->id,
				'name' => $name,
				'jmeno' => @$_POST['jmeno'],
				'link'  => @$_POST['link'],
			));

			return $presenter->redirectUrl("//$name.$site->domain/");

	}

	// neni zadana domena
	if ( ! $name) {
		$who = $db->table('who')->where(array('site' => $site->id))->order('RAND()')->fetch();
		return $presenter->redirectUrl("//$who->name.$site->domain/");
	}

	// create template
	$tpl = $presenter->createTemplate()
			->setFile(__DIR__ . '/app/site.latte');

	$tpl->site = $site;
	$tpl->who = $who;
	$tpl->name = $name;

	return $tpl;
});


// Run the application!
$container->application->run();
