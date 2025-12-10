<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\AutoArchive\AppInfo\Application::APP_ID, OCA\AutoArchive\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\AutoArchive\AppInfo\Application::APP_ID, OCA\AutoArchive\AppInfo\Application::APP_ID . '-main');

?>

<div id="auto_archive"></div>
