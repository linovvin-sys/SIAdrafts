var AuthController = {
  register: function (name, email, password, confirmPassword) {
    var formData = new FormData();
    formData.append('action', 'register');
    formData.append('name', name);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('confirm_password', confirmPassword);

    return fetch('./Backend/api/auth.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  login: function (email, password) {
    var formData = new FormData();
    formData.append('action', 'login');
    formData.append('email', email);
    formData.append('password', password);

    return fetch('./Backend/api/auth.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  logout: function () {
    var formData = new FormData();
    formData.append('action', 'logout');

    return fetch('./Backend/api/auth.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  checkSession: function () {
    return fetch('./Backend/api/auth.php?action=session').then(function (response) {
      return response.json();
    });
  },
};
