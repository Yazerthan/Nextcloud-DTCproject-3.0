<template>
  <div class="user-list">
    <h2>Utilisateurs Nextcloud</h2>
    <button @click="fetchUsers">Afficher les utilisateurs</button>

    <ul v-if="users.length">
      <li v-for="user in users" :key="user.uid">
        {{ user.displayName }} ({{ user.uid }})
      </li>
    </ul>

    <p v-else>Aucun utilisateur chargé</p>
  </div>
</template>

<script>
export default {
  name: 'UserList',
  data() {
    return {
      users: [],
    }
  },
  methods: {
    async fetchUsers() {
      try {
        const response = await fetch(
          OC.generateUrl('/ocs/v2.php/apps/helloworld/api/users'),
          {
            headers: {
              'OCS-APIREQUEST': 'true'
            }
          }
        )
        const json = await response.json()
        this.users = json.ocs.data
      } catch (e) {
        console.error('Erreur lors du chargement des utilisateurs', e)
      }
    }
  }
}
</script>

<style scoped>
.user-list {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100vw;
}

button {
  background-color: #0082c9;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 10px 20px;
  cursor: pointer;
}

button:hover {
  background-color: #006ba1;
}

ul {
  list-style: none;
  padding: 0;
  margin-top: 20px;
}

li {
  margin: 6px 0;
  background: #f4f4f4;
  padding: 8px;
  border-radius: 4px;
}
</style>
