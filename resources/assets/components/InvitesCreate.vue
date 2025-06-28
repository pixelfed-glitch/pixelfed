<template>
  <div>
    <h2>Create Invite</h2>
    <form @submit.prevent="submitForm">
      <input v-model="form.email" type="email" required placeholder="Email">
      <textarea v-model="form.message" placeholder="Message"></textarea>
      <button type="submit">Send Invite</button>
    </form>
  </div>
</template>
<script>
import axios from 'axios';
export default {
  data() {
    return { form: { email: '', message: '' } };
  },
  methods: {
    submitForm() {
      axios.post('/settings/invites/create', this.form)
        .then(() => this.$router.push('/settings/invites'))
        .catch(error => console.error(error));
    }
  }
}
</script>
