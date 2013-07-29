<?php
require_once "vendor/autoload.php";

use Testapp\App;

$app = new App();
$app->run();

$app->route->get("/", function(){
	return "Welcome to my awesome framework!";
});