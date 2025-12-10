<?php

declare(strict_types=1);

return [
    'routes' => [
        [
            'name' => 'autoarchive#moveOfficielToArchive',
            'url' => '/api/move-officiel',
            'verb' => 'GET',
            'class' => \OCA\AutoArchive\Controller\ApiController::class,
            'method' => 'moveOfficielToArchive'
        ],
        [
            'name' => 'autoarchive#getArchivesList',
            // L'URL de la requête qui vous posait problème
            'url' => '/api/archives/list',
            'verb' => 'GET',
            'class' => \OCA\AutoArchive\Controller\ApiController::class,
            'method' => 'getArchivesList'
        ],
        [
            'name' => 'autoarchive#checkArchiveStatus',
            // L'URL de la requête qui vous posait problème
            'url' => '/api/archive-status',
            'verb' => 'GET',
            'class' => \OCA\AutoArchive\Controller\ApiController::class,
            'method' => 'checkArchiveStatus'
        ],
    ]
];