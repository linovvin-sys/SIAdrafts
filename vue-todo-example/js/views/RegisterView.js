var { ref } = Vue;

var RegisterView = {
  setup() {
    var name = ref('');
    var email = ref('');
    var password = ref('');
    var confirmPassword = ref('');
    var errorMsg = ref('');

    function submit() {
      errorMsg.value = '';

      if (password.value !== confirmPassword.value) {
        errorMsg.value = 'Passwords do not match.';
        return;
      }

      AuthController.register(name.value, email.value, password.value, confirmPassword.value).then(function (data) {
        if (data.error) {
          errorMsg.value = data.error;
        } else {
          window.location.hash = '#/';
        }
      });
    }

    return { name, email, password, confirmPassword, errorMsg, submit };
  },
  template: `
    <div class="page">
      <h1>Register</h1>
      <form @submit.prevent="submit">
        <input v-model="name" placeholder="Name" required />
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="password" type="password" placeholder="Password" required />
        <input v-model="confirmPassword" type="password" placeholder="Confirm password" required />
        <button type="submit">Register</button>
      </form>
      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
      <p><router-link to="/login">Already have an account? Log in</router-link></p>
    </div>
  `,
};
