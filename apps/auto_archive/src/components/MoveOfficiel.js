/**
 * Appelle l'API Nextcloud pour déplacer le contenu du dossier "officiel"
 * vers un sous-dossier "année_courante" dans "archive".
 *
 * La fonction gère les erreurs réseau, les réponses non JSON et les erreurs
 * renvoyées par l'API, et retourne toujours un objet avec message et type.
 *
 * @returns {Promise<{ message: string, type: 'success' | 'error' | 'info' }>}
 */
export async function moveOfficielAPI() {
	try {
		const response = await fetch(
			// L'URL de l'API est correcte et correspond à routes.php
			'/ocs/v2.php/apps/auto_archive/api/move-officiel?format=json',
			{
				method: 'GET',
				headers: {
					'OCS-APIREQUEST': 'true',
					'Accept': 'application/json'
				}
			}
		);

		if (!response.ok) {
			return {
				message: `Erreur réseau : ${response.status} ${response.statusText}`,
				type: 'error'
			};
		}

		let json;
		try {
			json = await response.json();
		} catch {
			return { message: 'Erreur : la réponse de l’API n’est pas au format JSON', type: 'error' };
		}

		if (!json.ocs || !json.ocs.meta) {
			return { message: 'Erreur : réponse API mal formée', type: 'error' };
		}

		const status = json.ocs.meta.statuscode;
		// La DataResponse du PHP renvoie les messages dans json.ocs.data.message
		const apiMessage = json.ocs.data?.message || json.ocs.meta.message || '';

		if (status === 200 || status === 201) {
			if (apiMessage.toLowerCase().includes('vide')) {
				return { message: 'Le dossier "officiel" est vide, rien à déplacer.', type: 'info' };
			}
			return { message: apiMessage || 'Documents déplacés avec succès.', type: 'success' };
		} else if (status === 404) {
			// Le 404 est géré par l'API PHP si le dossier "officiel" n'existe pas
			return { message: 'Le dossier "officiel" est introuvable.', type: 'error' };
		} else if (status === 403) {
			return { message: 'Permission refusée pour déplacer le dossier "officiel".', type: 'error' };
		} else {
			return { message: apiMessage || `Erreur API inattendue (code ${status})`, type: 'error' };
		}

	} catch (e) {
		console.error('Erreur API move-officiel :', e);
		return {
			message: 'Erreur lors de l’appel API. Vérifiez votre connexion ou la configuration du serveur.',
			type: 'error'
		};
	}
}

/**
 * Appelle l'API Nextcloud pour obtenir la liste des archives.
 *
 * @returns {Promise<{ data: Array<{ name: string, date: string }>, type: 'success' | 'error' | 'info' }>}
 */
export async function getArchivesListAPI() {
	try {
		const response = await fetch(
			'/ocs/v2.php/apps/auto_archive/api/archives/list?format=json',
			{
				method: 'GET',
				headers: {
					'OCS-APIREQUEST': 'true',
					'Accept': 'application/json'
				}
			}
		);

		if (!response.ok) {
			return { data: [], type: 'error', message: `Erreur réseau : ${response.status} ${response.statusText}` };
		}

		let json;
		try {
			json = await response.json();
		} catch {
			return { data: [], type: 'error', message: 'Erreur : la réponse de l’API n’est pas au format JSON' };
		}

		if (json.ocs && json.ocs.meta.statuscode === 200) {
			// L'API Nextcloud renvoie les données de la DataResponse dans json.ocs.data.data
			return { 
				data: json.ocs.data.data || [], 
				type: 'success',
				message: json.ocs.data.message || 'Liste des archives récupérée.'
			};
		} else {
			const apiMessage = json.ocs.meta.message || 'Erreur API inattendue.';
			return { data: [], type: 'error', message: apiMessage };
		}

	} catch (e) {
		console.error('Erreur API get-archives-list :', e);
		return {
			data: [],
			type: 'error',
			message: 'Erreur lors de l’appel API pour la liste des archives.'
		};
	}
}

/**
 * Appelle l'API pour vérifier si une archive est due (plus d'un an).
 *
 * @returns {Promise<{ showAlert: boolean, message: string }>}
 */
export async function checkArchiveStatusAPI() {
    try {
        const response = await fetch(
            '/ocs/v2.php/apps/auto_archive/api/archive-status?format=json',
            {
                method: 'GET',
                headers: {
                    'OCS-APIREQUEST': 'true',
                    'Accept': 'application/json'
                }
            }
        );

        if (!response.ok) {
            console.error('Erreur réseau checkArchiveStatus:', response.status);
            return { showAlert: false, message: 'Erreur réseau lors de la vérification du statut.' };
        }

        const json = await response.json();

        if (json.ocs && json.ocs.meta.statuscode === 200) {
            return {
                showAlert: json.ocs.data.showAlert || false,
                message: json.ocs.data.message || 'Statut d\'archive vérifié.',
            };
        } else {
            console.error('Erreur API checkArchiveStatus:', json.ocs.meta.message);
            return { showAlert: false, message: 'Erreur inattendue de l\'API.' };
        }

    } catch (e) {
        console.error('Erreur lors de l’appel API checkArchiveStatus:', e);
        return {
            showAlert: false,
            message: 'Erreur critique lors de la vérification du statut.'
        };
    }
}