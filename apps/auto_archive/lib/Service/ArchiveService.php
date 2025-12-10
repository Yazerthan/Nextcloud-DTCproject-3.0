<?php

declare(strict_types=1);

namespace OCA\AutoArchive\Service;

use OCP\IUserManager;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OCP\Notification\IManager;
use OCP\Mail\IMailer;
use OCP\IConfig;
use OCP\IUser;
use OCP\Defaults;
use OCP\IURLGenerator;

class ArchiveService {

    private IUserManager $userManager;
    private IRootFolder $rootFolder;
    private IManager $notificationManager;
    private IMailer $mailer;
    private IConfig $config;
    private Defaults $defaults;
    private IURLGenerator $urlGenerator;
    private const APP_ID = 'auto_archive';

    public function __construct(
        IUserManager $userManager,
        IRootFolder $rootFolder,
        IManager $notificationManager,
        IMailer $mailer,
        IConfig $config,
        Defaults $defaults,
        IURLGenerator $urlGenerator
    ) {
        $this->userManager = $userManager;
        $this->rootFolder = $rootFolder;
        $this->notificationManager = $notificationManager;
        $this->mailer = $mailer;
        $this->config = $config;
        $this->defaults = $defaults;
        $this->urlGenerator = $urlGenerator;
    }

    // Fichier : lib/Service/ArchiveService.php

public function isArchiveDue(IUser $user): bool {
    $lastTimestamp = $this->getLastArchiveTimestamp($user);
    
    // Si aucune archive trouvée, on alerte (ou pas, voir plus bas)
    if ($lastTimestamp === 0) {
         return false; 
    }

    $threshold = strtotime('-360 days'); 
    
    // Si le dernier timestamp est plus vieux que le seuil, l'archive est due.
    return $lastTimestamp < $threshold;
}

    /**
     * Envoie les notifications
     */
    public function sendAlerts(IUser $user): void {
        $uid = $user->getUID();
        error_log("AutoArchive DEBUG: sendAlerts appelé pour UID: " . $uid);

        // --- Anti-spam (Commenté pour le test - A DÉCOMMENTER EN PROD) ---
        // $lastSent = (int)$this->config->getUserValue($uid, self::APP_ID, 'last_reminder_sent', '0');
        // if (time() - $lastSent < 60 * 60 * 24 * 7) {
        //    return; 
        // }

        $adminUid = 'admin';

        if ($uid !== $adminUid) {
            error_log("AutoArchive TEST: Utilisateur $uid ignoré (n'est pas l'admin $adminUid).");
            return;
        }

        // 1. Notification Nextcloud 
        // On utilise un bloc try/catch avec Throwable pour que si ça plante, ça ne bloque pas l'email
        try {
            if (method_exists($this->notificationManager, 'create')) {
                $notification = $this->notificationManager->create();
                $notification->setApp(self::APP_ID)
                    ->setUser($uid)
                    ->setDateTime(new \DateTime())
                    ->setObject('archive_due', 'year')
                    ->setSubject('notification_archive_due')
                    ->setRichText('Votre dossier officiel n\'a pas été archivé depuis plus d\'un an.')
                    ->setLink($this->urlGenerator->linkToRouteAbsolute('auto_archive.page.index'));
    
                $this->notificationManager->notify($notification);
                error_log("AutoArchive DEBUG: Notification Push envoyée.");
            } else {
                error_log("AutoArchive WARNING: La méthode create() n'existe pas sur le NotificationManager.");
            }
        } catch (\Throwable $e) {
            error_log("AutoArchive ERREUR NOTIF (Ignorée): " . $e->getMessage());
        }

        // 2. Email (Le plus important)
        $email = $user->getEMailAddress();
        if (!empty($email)) {
            $this->sendEmail($user, $email);
        } else {
            error_log("AutoArchive ALERTE: Pas d'email configuré pour " . $uid);
        }

        // Mise à jour timestamp
        $this->config->setUserValue($uid, self::APP_ID, 'last_reminder_sent', (string)time());
    }

    private function sendEmail(IUser $user, string $emailAddress): void {
        try {
            $template = $this->mailer->createEMailTemplate('auto_archive.ArchiveReminder');
            $template->setSubject('Action requise : Archivage de vos documents');
            $template->addHeader();
            $template->addHeading('Rappel d\'archivage');
            $template->addBodyText('Bonjour ' . htmlspecialchars($user->getDisplayName()) . ', il est temps d\'archiver votre dossier officiel.');
            $template->addBodyButton('Archiver maintenant', $this->urlGenerator->linkToRouteAbsolute('auto_archive.page.index'));
            $template->addFooter();

            $message = $this->mailer->createMessage();
            $message->setTo([$emailAddress => $user->getDisplayName()]);
            $message->useTemplate($template);
            
            $this->mailer->send($message);
            error_log("AutoArchive DEBUG: Email envoyé avec succès à " . $emailAddress);
        } catch (\Throwable $e) {
            error_log('AutoArchive ERREUR EMAIL: ' . $e->getMessage());
        }
    }

    /**
 * Récupère la date (timestamp) de la dernière archive trouvée.
 * @return int 0 si aucune archive trouvée.
 */
public function getLastArchiveTimestamp(IUser $user): int {
    $uid = $user->getUID();
    
    try {
        $userFolder = $this->rootFolder->getUserFolder($uid);
    } catch (\Exception $e) {
        return 0;
    }

    if (!$userFolder->nodeExists('archive')) {
        return 0;
    }

    $archiveFolder = $userFolder->get('archive');
    $lastArchiveTime = 0; 
    
    foreach ($archiveFolder->getDirectoryListing() as $node) {
        if ($node instanceof Folder) {
            if ($node->getMtime() > $lastArchiveTime) {
                $lastArchiveTime = $node->getMtime();
            }
        }
    }
    return $lastArchiveTime;
}

}

