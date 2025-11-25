<template>
  <div class="user-list">
    <h2>Utilisateurs Nextcloud</h2>

    <div class="container" v-if="showUsers">
      <p class="userText">Nos utilisateurs</p>
      <div class="user__container">
        <button class="user_button" v-for="user in users" :key="user.uid">
          {{ user.displayName }} ({{ user.uid }})
        </button>
      </div>
    </div>

    <button class="showUser" @click="fetchUsers">
      {{ showUsers ? 'Cacher les utilisateurs' : 'Afficher les utilisateurs' }}
    </button>
  </div>
</template>


<script>
export default {
  name: 'UserList',
  data() {
    return {
      users: [],
      showUsers: false,
      loaded: false,
      showUserName: false,
    }
  },
  methods: {
    async fetchUsers() {
      if (this.loaded) {
        this.showUsers = !this.showUsers
        return
      }

      try {
        const response = await fetch(
          '/ocs/v2.php/apps/helloworld/api/users?format=json',
          {
            headers: {
              'OCS-APIREQUEST': 'true',
              'Accept': 'application/json'
            }
          }
        )

        const json = await response.json()

        this.users = json.ocs.data
        this.loaded = true 
        this.showUsers = true

      } catch (e) {
        console.error('Erreur lors du chargement des utilisateurs', e)
      }
    }
  },
  // methods: {
  //   async UserName(){
  //     if (!showUserName) {
  //       showUserName = true;
  //     } else {
  //       showUserName = false;
  //     }
  //   }

  // }
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

h2 {
  margin-bottom: 6px;
}

.container{
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
  margin: 20px 0;
}

.userText {
  font-size: x-large;
}

.user__container {
  display: flex;
  flex-direction: row;
  gap: 1.5rem;
}

.user_button {
  background: #fff;
  color: #00679e;
  border-radius: 8px;
  border: 1px solid #00679e;
}

.showUser {
  background-color: #0082c9;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 10px 20px;
  cursor: pointer;
  margin: 6px 0 0 !important;
}

.showUser:hover {
  background-color: #006ba1;
}

</style>
