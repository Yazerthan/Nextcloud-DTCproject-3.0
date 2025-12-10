<?php

declare(strict_types=1);

namespace OCA\AutoArchive\Cron;

use OCA\AutoArchive\Service\ArchiveService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;

class CheckArchiveJob extends TimedJob {

    private ArchiveService $archiveService;
    private IUserManager $userManager;

    public function __construct(
        ITimeFactory $time,
        ArchiveService $archiveService,
        IUserManager $userManager
    ) {
        parent::__construct($time);
        $this->setInterval(24 * 60 * 60);
        $this->archiveService = $archiveService;
        $this->userManager = $userManager;
    }

    public function run($argument): void {
        error_log("AutoArchive CRON: Démarrage du Job.");

        $this->userManager->callForSeenUsers(function($user) {
            try {
                if ($this->archiveService->isArchiveDue($user)) {
                    $this->archiveService->sendAlerts($user);
                }
            } catch (\Throwable $e) {
                error_log('AutoArchive CRON ERROR pour ' . $user->getUID() . ': ' . $e->getMessage());
            }
        });
    }
}