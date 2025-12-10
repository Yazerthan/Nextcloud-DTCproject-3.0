<?php

declare(strict_types=1);

namespace OCA\AutoArchive\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;
use OCA\AutoArchive\Cron\CheckArchiveJob;

class Application extends App implements IBootstrap {
    public const APP_ID = 'auto_archive';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // On laisse l'injection de dépendance automatique gérer les services
    }

    public function boot(IBootContext $context): void {
        // Enregistrement du Job via IJobList (Méthode robuste)
        $container = $this->getContainer();
        
        /** @var IJobList $jobList */
        $jobList = $container->query(IJobList::class);
        
        // On ajoute le job à la liste d'exécution
        $jobList->add(CheckArchiveJob::class);
    }
}