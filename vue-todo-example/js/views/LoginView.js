var { ref } = Vue;

var LoginView = {
  setup() {
    var email = ref('');
    var password = ref('');
    var errorMsg = ref('');

    function submit() {
      errorMsg.value = '';
      AuthController.login(email.value, password.value).then(function (data) {
        if (data.error) {
          errorMsg.value = data.error;
        } else {
          window.location.hash = '#/';
        }
      });
    }

    return { email, password, errorMsg, submit };
  },
  template: `
    <div class="page">
      <h1>Log In</h1>
      <form @submit.prevent="submit">
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="password" type="password" placeholder="Password" required />
        <button type="submit">Log In</button>
      </form>
      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
      <p><router-link to="/register">Need an account? Register</router-link></p>
    </div>
  `,
};
