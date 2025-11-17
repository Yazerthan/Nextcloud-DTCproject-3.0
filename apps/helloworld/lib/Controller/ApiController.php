<?php

declare(strict_types=1);

namespace OCA\HelloWorld\Controller;

use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserManager;

class ApiController extends OCSController {
    private IUserManager $userManager;

    public function __construct(string $AppName, IRequest $request, IUserManager $userManager) {
        parent::__construct($AppName, $request);
        $this->userManager = $userManager;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[ApiRoute(verb: 'GET', url: '/api')]
    public function index(): DataResponse {
        return new DataResponse(['message' => 'Hello world!']);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[ApiRoute(verb: 'GET', url: '/api/users')]
    public function getUsers(): DataResponse {
        $users = [];
        foreach ($this->userManager->search('') as $user) {
            $users[] = [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName(),
            ];
        }
        return new DataResponse($users);
    }
}
