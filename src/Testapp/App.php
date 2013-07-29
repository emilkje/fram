<?php

namespace Testapp;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Testapp\REST\Request;
use Testapp\REST\Router;
use Testapp\REST\Route;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

class App {

	public $router;
	public $request;
	private $accept;

	public function __construct() {
		$this->request = Request::createFromGlobals();
		$this->router = new Router($this->request);
		$this->route = new Route($this->router);
	}

	function run() {
		$whoops = new Run();
		$errorPage = new PrettyPageHandler();
		$errorPage->setEditor("sublime");
		$whoops->pushHandler($errorPage);
		$whoops->register();

		$accept = AcceptHeader::fromString($this->request->headers->get('Accept'));
		$dotjson = substr($this->request->getPathInfo(), -5) === ".json";
		if ($accept->has('application/json') || $accept->has('json') || $dotjson) {
		    $this->accept = "json";
		} else {
			$this->accept = "html";
		}

		$this->log = new Logger('foo');
		$this->log->pushHandler(new StreamHandler('log/foo.log', Logger::WARNING));
	}
}