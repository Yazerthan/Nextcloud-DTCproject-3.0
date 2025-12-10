<?php

declare(strict_types=1);

namespace OCA\AutoArchive\Controller;

use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Lock\ILockingProvider;
use OCP\Notification\IManager as INotificationManager;
use OCA\AutoArchive\Service\ArchiveService;

class ApiController extends OCSController {
    private IUserManager $userManager;
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private ILockingProvider $lockProvider;
    private INotificationManager $notificationManager;
    private ArchiveService $archiveService;

    public function __construct(
        string $AppName,
        IRequest $request,
        IUserManager $userManager,
        IRootFolder $rootFolder,
        IUserSession $userSession,
        ILockingProvider $lockProvider,
        INotificationManager $notificationManager,
        ArchiveService $archiveService
    ) {
        parent::__construct($AppName, $request);
        $this->userManager = $userManager;
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->lockProvider = $lockProvider;
        $this->notificationManager = $notificationManager;
        $this->archiveService = $archiveService;
    }

#[LoginRequired]
#[ApiRoute(verb: 'GET', url: '/api/move-officiel')] 
public function moveOfficielToArchive(): DataResponse {
    $user = $this->userSession->getUser();
    if (!$user) {
        // 1. UTILISATEUR NON CONNECTÉ
        return new DataResponse(['status' => 'error', 'message' => 'Utilisateur non connecté'], 401);
    }

    $uid = $user->getUID();
    $userFolder = $this->rootFolder->getUserFolder($uid);

    // 2. DOSSIER SOURCE INTROUVABLE
    if (!$userFolder->nodeExists('officiel')) {
        return new DataResponse(['status' => 'error', 'message' => 'Dossier "officiel" introuvable'], 404);
    }

    try {
        // 3. VÉRIFICATION ET CRÉATION DU DOSSIER 'archive'
        if (!$userFolder->nodeExists('archive')) {
            $archive = $userFolder->newFolder('archive');
        } else {
            $archive = $userFolder->get('archive');
        }
        
        // VERIFICATION D'ACCÈS
        if (!$archive || !($archive instanceof \OCP\Files\Folder)) {
            return new DataResponse(['status' => 'error', 'message' => 'Erreur critique: Dossier "archive" inaccessible ou non-créé.'], 500);
        }

        // DÉFINITION DE LA DESTINATION (AAAA_MM)
        $yearFolderName = date('Y') . '-' . date('m');
        
        // 4. VÉRIFICATION ET CRÉATION DU SOUS-DOSSIER D'ARCHIVAGE
        if (!$archive->nodeExists($yearFolderName)) {
            $yearFolder = $archive->newFolder($yearFolderName);
        } else {
            $yearFolder = $archive->get($yearFolderName);
        }

        // VERIFICATION D'ACCÈS
        if (!$yearFolder || !($yearFolder instanceof \OCP\Files\Folder)) {
            return new DataResponse(['status' => 'error', 'message' => 'Erreur critique: Sous-dossier d\'archivage inaccessible ou non-créé.'], 500);
        }

        $officiel = $userFolder->get('officiel');
        $listing = $officiel->getDirectoryListing();

        // 5. DOSSIER SOURCE VIDE
        if (empty($listing)) {
            return new DataResponse(['status' => 'ok', 'message' => 'Le dossier "officiel" est vide, rien à déplacer.']);
        }

        // Déplacement des fichiers
        $filesMovedCount = 0;
       $errors = [];
        
        foreach ($listing as $node) {
            $targetPath = $yearFolder->getPath() . '/' . $node->getName();
            
            try {
                $node->move($targetPath);
                $filesMovedCount++;
            } 
            // 6. GESTION DES ERREURS SPÉCIFIQUES NEXTCLOUD
            catch (\OCP\Lock\LockedException $e) {
                $errors[] = 'Erreur: Le fichier ' . $node->getName() . ' est verrouillé par un autre utilisateur ou une autre application. Déplacement annulé.';
            } 
            catch (\OCP\Files\InvalidPathException $e) {
                $errors[] = 'Erreur: Chemin invalide pour le fichier ' . $node->getName() . '.';
            } 
            catch (\OCP\Files\NotPermittedException $e) {
                $errors[] = 'Erreur: Permissions insuffisantes pour déplacer ' . $node->getName() . '.';
            } 
            catch (\Exception $e) {
                // 7. CATCH TOUT AUTRE ERREUR DE DÉPLACEMENT
                $errors[] = 'Erreur non spécifiée lors du déplacement de ' . $node->getName() . ': ' . $e->getMessage();
                error_log('AutoArchive: Erreur de déplacement pour ' . $node->getName() . ': ' . $e->getMessage()); 
            }
        }
        
        if ($filesMovedCount > 0) {
            try {
                // 8. ERREURS LORS DU VERROUILLAGE
                $this->setReadOnly($yearFolder);
            } 
            catch (\Exception $e) {
                $errors[] = 'Erreur critique lors de l\'application du mode lecture seule sur le dossier: ' . $e->getMessage();
                error_log('AutoArchive: Erreur fatale setReadOnly: ' . $e->getMessage()); 
            }
        }

        $response = [
            'status' => 'ok',
            'message' => "{$filesMovedCount} élément(s) du dossier « officiel » ont été déplacés vers le dossier « archive/{$yearFolderName} »."
        ];

        if (!empty($errors)) {
            // Si des fichiers ont été déplacés malgré les erreurs de d'autres fichiers/verrouillage
            $response['status'] = ($filesMovedCount > 0) ? 'warning' : 'error';
            $response['message'] .= ' Attention : Des erreurs sont survenues lors du déplacement ou du verrouillage.';
            $response['errors'] = $errors;
        }
        
        // 9. CATCH GÉNÉRAL EN CAS D'ERREUR DU SYSTÈME DE FICHIERS AVANT LA BOUCLE
        return new DataResponse($response);
    } catch (\OCP\Files\NotFoundException $e) {
        // Erreur inattendue de l'arborescence
        error_log('AutoArchive: NotFoundException: ' . $e->getMessage());
        return new DataResponse(['status' => 'error', 'message' => 'Erreur critique du système de fichiers: Un dossier n\'a pas pu être trouvé.'], 500);
    }
}

    /**
     * Récupère la liste des sous-dossiers (années) dans le dossier 'archive'.
     *
     * @return DataResponse
     */
    #[LoginRequired]
    #[ApiRoute(verb: 'GET', url: '/api/archives/list')] 
    public function getArchivesList(): DataResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new DataResponse(['status' => 'error', 'message' => 'Utilisateur non connecté'], 401);
        }

        $uid = $user->getUID();
        $userFolder = $this->rootFolder->getUserFolder($uid);
        
        // Vérifie si le dossier 'archive' existe
        if (!$userFolder->nodeExists('archive')) {
            // Si le dossier n'existe pas, l'utilisateur n'a pas encore archivé.
            return new DataResponse([
                'status' => 'ok', 
                'message' => 'Aucune archive trouvée.', 
                'data' => [] // Retourne un tableau vide
            ]);
        }
        
        $archiveFolder = $userFolder->get('archive');
        $archivesList = [];
        
        // Parcourt les éléments dans le dossier 'archive'
        foreach ($archiveFolder->getDirectoryListing() as $node) {
            // On ne s'intéresse qu'aux dossiers (les archives sont des dossiers par année)
            if ($node instanceof Folder) { // Utilisation du typehint importé
                $archivesList[] = [
                    'name' => $node->getName(),
                    // Utiliser le mtime (modification time) pour la date de création/dernière modification
                    'timestamp' => $node->getMtime(), 
                    'date' => date('Y-m-d H:i:s', $node->getMtime()),
                ];
            }
        }
        
        // Triez la liste par date décroissante (plus récente en premier)
        usort($archivesList, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return new DataResponse([
            'status' => 'ok',
            'message' => count($archivesList) . ' archives trouvées.',
            'data' => $archivesList
        ]);
    }


    /**
     * Applique un verrouillage partagé (lecture seule) sur le dossier et son contenu de manière récursive.
     * Le verrouillage empêche la modification, le renommage ou la suppression du fichier/dossier.
     * * @param \OCP\Files\Folder $folder Le dossier à verrouiller.
     */
    private function setReadOnly(Folder $folder) {
        try {
            // 1. Verrouiller le dossier lui-même
            $this->lockProvider->lock($folder->getPath(), ILockingProvider::LOCK_SHARED);

            $listing = $folder->getDirectoryListing();
            
            foreach ($listing as $node) {
                // Si c’est un dossier, récursivité
                if ($node instanceof \OCP\Files\Folder) {
                    $this->setReadOnly($node);
                }
                
                // 2. Verrouiller le fichier/sous-dossier
                // LOCK_SHARED empêche l'écriture et la suppression par le propriétaire
                $this->lockProvider->lock($node->getPath(), ILockingProvider::LOCK_SHARED); 
            }
            
        } catch (\Throwable $e) { 
        error_log('AutoArchive: Erreur lors du verrouillage de ' . $folder->getPath() . ': ' . $e->getMessage());
    }
    }

#[LoginRequired]
#[ApiRoute(verb: 'GET', url: '/api/archive-status')] 
public function checkArchiveStatus(): DataResponse {
    $user = $this->userSession->getUser();
    if (!$user) {
        return new DataResponse(['status' => 'error'], 401);
    }

    // 1. Déléguer la vérification si l'archive est due (Année Civile)
    $isDue = $this->archiveService->isArchiveDue($user);
    $message = $isDue ? 'Avertissement: Archive requise.' : 'Statut OK.';

    // 2. Récupérer la date de la dernière archive pour le Frontend
    $lastTimestamp = $this->archiveService->getLastArchiveTimestamp($user);
    
    // Formater la date comme attendu par VueJS
    $displayDate = ($lastTimestamp > 0) ? date('Y-m-d', $lastTimestamp) : 'N/A';

    return new DataResponse([
        'showAlert' => $isDue,
        'lastArchiveDate' => $displayDate,
        'message' => $message,
    ]);
}

}