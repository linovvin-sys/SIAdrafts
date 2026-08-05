var AuthController = {
  register: function (name, email, password, confirmPassword) {
    var formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('confirm_password', confirmPassword);

    return fetch('./Backend/api/register.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  login: function (email, password) {
    var formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    return fetch('./Backend/api/login.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  logout: function () {
    return fetch('./Backend/api/logout.php', {
      method: 'POST',
    }).then(function (response) {
      return response.json();
    });
  },

  checkSession: function () {
    return fetch('./Backend/api/session.php').then(function (response) {
      return response.json();
    });
  },
};
