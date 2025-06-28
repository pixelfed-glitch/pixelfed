<template>
  <div>
    <h2>My Invites</h2>
    <table v-if="invites.length">
      <tr v-for="invite in invites" :key="invite.id">
        <td>{{ invite.email }}</td>
        <td>{{ invite.message }}</td>
        <td><button @click="deleteInvite(invite.id)">Delete</button></td>
      </tr>
    </table>
    <p v-else>No invites found.</p>
    <router-link to="/settings/invites/create">Invite Someone</router-link>
  </div>
</template>
<script>
import axios from 'axios';
export default {
  data() {
    return { invites: [] };
  },
  mounted() {
    this.fetchInvites();
  },
  methods: {
    fetchInvites() {
      axios.get('/settings/invites', { headers: { 'Accept': 'application/json' } })
        .then(response => { this.invites = response.data; })
        .catch(error => console.error(error));
    },
    deleteInvite(id) {
      axios.post('/settings/invites/delete', { id })
        .then(() => this.fetchInvites())
        .catch(error => console.error(error));
    }
  }
}
</script>
