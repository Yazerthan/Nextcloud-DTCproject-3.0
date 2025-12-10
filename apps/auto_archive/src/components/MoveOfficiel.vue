<template>
  <div class="move-officiel-container">
    <div v-if="isArchiveDue" class="warning-card">
      <div class="warning-content">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <p><strong>{{ archiveDueMessage }}</strong></p>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <h2>Archivage</h2>
        <p class="subtitle">Gérez l'archivage de vos documents en un clic.</p>
        <button class="readme-button" @click="openReadmeModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
          Comment ça marche ?
        </button>
      </div>
      <div class="card-body">
        <button 
          class="action-button" 
          @click="openConfirmModal" 
          :disabled="isLoading"
          :class="{ 'is-loading': isLoading }"
          aria-label="Déplacer le dossier officiel vers l'archive"
        >
          <span v-if="!isLoading" class="btn-content">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Archiver maintenant
          </span>
          <span v-else class="loader"></span>
        </button>
        <transition name="fade">
          <div v-if="apiMessage" class="status-message" :class="messageType">
            <div class="status-icon">
              <svg v-if="messageType === 'success'" xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              <svg v-else-if="messageType === 'error'" xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <span>{{ apiMessage }}</span>
          </div>
        </transition>
      </div>
    </div>

    <div v-if="!isArchivesLoading && archives.length > 0" class="card-archive archives-list-card">
      <div class="archives-list-container-title">
        <h3>Historique des archives</h3>
        <p>
          Nombre d'archive{{ archives.length > 1 ? 's' : '' }} réalisé{{ archives.length > 1 ? 's' : '' }} :
          {{ archives.length }}
        </p>
      </div>    
      <ul class="archives-list">
        <li v-for="(archive, index) in archives" :key="archive.name">
          <span class="archive-date">
            <template v-if="index === 0">
              Dernière archive : 
            </template>
            <template v-else>
              Archive du : 
            </template>
            {{ archive.date.split(' ')[0] }}
          </span>
          <span class="archive-name">
            Dossier : `archive/{{ archive.name }}`
          </span>
        </li>
      </ul>
    </div>
    
    <div v-else-if="!isArchivesLoading && archives.length === 0" class="info-card">
      {{ noArchivesMessage }}
    </div>

    <div v-else-if="isArchivesLoading" class="info-card">
      <span class="loader small-loader"></span> Chargement de l'historique...
    </div>

    <transition name="modal-fade">
      <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content">
          <h3 id="modalTitle" class="modal-title">Confirmation d'archivage</h3>
          <p class="modal-message">
            Êtes-vous certain de vouloir archiver le contenu du dossier « officiel » ?
          </p>
          <div class="consequence-box">
            <p class="consequence-text">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="consequence-icon"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
              <strong>L'archivage de votre dossier « officiel » est irréversible : </strong>
            </p>
            <ul class="consequence-list">
              <li>Les fichiers seront déplacés vers le dossier `archive/{{ currentYear }}`.</li>
              <li>Vous pourrez consulter les fichiers déplacés, mais pas les modifier.</li>
              <li>Toute modification, suppression ou ajout dans le dossier archivé sera impossible.</li>
            </ul>
          </div>
          <div class="modal-actions">
            <button class="btn-secondary" @click="closeModal">Annuler l'archivage</button>
            <button class="btn-primary" @click="confirmAndArchive" :disabled="isLoading">
              Confirmer et archiver
            </button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="modal-fade">
    <div v-if="isReadmeModalOpen" class="modal-overlay" @click.self="closeReadmeModal" role="dialog" aria-modal="true" aria-labelledby="readmeModalTitle">
        <div class="modal-content">
            <h3 id="readmeModalTitle" class="modal-title">Fonctionnement de l'archivage</h3>
            <div class="readme-content">
                <p>Ce mécanisme permet de <strong>clôturer définitivement</strong> le contenu de votre dossier <strong>« officiel »</strong> afin de garantir sa pérennité et son intégrité.</p>
                
                <h4>Quand archiver ?</h4>
                <p>Il est recommandé de procéder à l'archivage à la fin de chaque <strong>cycle majeur</strong> (par exemple, la fin d'une année fiscale ou administrative).</p>

                <h4>Étapes de l'archivage</h4>
                <ol class="readme-list">
                    <li>L'utilisateur clique sur <strong>« Archiver maintenant »</strong>.</li>
                    <li>Une fenêtre de <strong>confirmation</strong> s'ouvre, rappelant le caractère irréversible de l'action.</li>
                    <li>Après confirmation, le processus d'archivage démarre.</li>
                    <li>Les fichiers du dossier « officiel » sont déplacés vers un <strong>dossier d'archive horodaté</strong> (ex: archive/{{ currentYear }}).</li>
                    <li>Un message de succès ou d'erreur s'affiche, et l'<strong>historique des archives</strong> est mis à jour.</li>
                </ol>
                
                <h4>Conséquences IRRÉVERSIBLES</h4>
                <p>Une fois archivé, l'intégrité de vos documents est protégée, mais leur modification devient impossible :</p>
                <ul class="consequence-list">
                    <li>Les fichiers déplacés sont <strong>verrouillés en lecture seule</strong>.</li>
                    <li>Vous ne pouvez plus <strong>modifier, supprimer ou ajouter</strong> de documents dans le dossier d'archive.</li>
                    <li>Le dossier <strong>« officiel »</strong> est vidé pour accueillir de nouveaux documents pour le cycle en cours.</li>
                </ul>
                
                <p>Assurez-vous que tous les documents sont finaux avant de confirmer l'archivage.</p>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary full-width" @click="closeReadmeModal">Fermer l'aide</button>
            </div>
        </div>
    </div>
</transition>
  </div>
</template>

<script>
import { moveOfficielAPI, getArchivesListAPI, checkArchiveStatusAPI } from './MoveOfficiel.js';
import './MoveOfficiel.css';

export default {
    name: 'MoveOfficiel',
    data() {
        return {
            apiMessage: '',
            isLoading: false,
            messageType: 'info',
            isModalOpen: false, 
            isReadmeModalOpen: false, 
            currentYear: new Date().getFullYear(),
            archives: [], 
            isArchivesLoading: false,
            isArchiveDue: false,
            archiveDueMessage: '',
        };
    },
    computed: {
        noArchivesMessage() {
            return this.isArchivesLoading ? 'Chargement...' : 'Aucune archive effectuée pour le moment.';
        }
    },
    methods: {
        openConfirmModal() {
            this.isModalOpen = true;
            this.apiMessage = ''; 
        },


        closeModal() {
            this.isModalOpen = false;
        },

        openReadmeModal() {
            this.isReadmeModalOpen = true;
        },

        closeReadmeModal() {
            this.isReadmeModalOpen = false;
        },

async checkStatus() {
        try {
            const result = await checkArchiveStatusAPI();
            this.isArchiveDue = result.showAlert;
            
            if (this.isArchiveDue) {
                // 1. On récupère la date (vient du PHP, doit être dans la DataResponse)
                const lastDateStr = result.lastArchiveDate;
                let daysMessage = "Aucune archive récente détectée."; // Default message

                // 2. Calcul du nombre de jours si la date existe
                if (lastDateStr && lastDateStr !== 'N/A') {
                    const lastDate = new Date(lastDateStr);
                    const today = new Date();
                    
                    const diffTime = Math.abs(today - lastDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    daysMessage = `Cela fait ${diffDays} jours que vous n'avez pas archivé.`;
                }
                
                // 3. Construction de la phrase finale
                this.archiveDueMessage = `${daysMessage} Il est vivement recommandé de clôturer la période en cours.`;
                
            } else {
                this.archiveDueMessage = '';
            }
        } catch (error) {
            console.error('Erreur lors de la vérification du statut:', error);
        }
    },
    

        async loadArchivesList() {
            this.isArchivesLoading = true;
            try {
                const result = await getArchivesListAPI();
                if (result.type === 'success') {
                    this.archives = result.data;
                } else {
                    // En cas d'erreur ou d'absence d'archive, on vide la liste
                    this.archives = [];
                }
            } catch (error) {
                console.error('Erreur lors du chargement des archives:', error);
            } finally {
                this.isArchivesLoading = false;
            }
        },

        async confirmAndArchive() {
            this.closeModal(); 
            
            this.isLoading = true;
            this.apiMessage = 'Déplacement en cours...';
            this.messageType = 'info';

            try {
                const { message, type } = await moveOfficielAPI();
                this.apiMessage = message;
                this.messageType = type;

                // Recharge la liste des archives si le déplacement a réussi (ou s'il était vide)
                if (type === 'success' || type === 'info') {
                    await this.loadArchivesList(); 
                }

            } catch (error) {
                this.messageType = 'error';
                this.apiMessage = 'Une erreur est survenue lors de l\'archivage.';
                console.error('Erreur API lors de l\'archivage:', error);
            } finally {
                this.isLoading = false;
            }
        }
    },
    mounted() {
        this.loadArchivesList(); 
        this.checkStatus();
    }
};
</script>