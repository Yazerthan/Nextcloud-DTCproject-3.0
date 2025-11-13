<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\HelloWorld\AppInfo\Application::APP_ID, OCA\HelloWorld\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\HelloWorld\AppInfo\Application::APP_ID, OCA\HelloWorld\AppInfo\Application::APP_ID . '-main');

?>

<div id="helloworld"></div>
