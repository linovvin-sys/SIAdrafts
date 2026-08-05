var TaskController = {
  getTasks: function () {
    return fetch('./Backend/api/get_tasks.php').then(function (response) {
      return response.json();
    });
  },

  createTask: function (task) {
    var formData = new FormData();
    formData.append('title', task.title);
    formData.append('notes', task.notes);
    formData.append('category', task.category);
    formData.append('status', task.status);

    return fetch('./Backend/api/create_task.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  updateTask: function (task) {
    var formData = new FormData();
    formData.append('id', task.id);
    formData.append('title', task.title);
    formData.append('notes', task.notes);
    formData.append('category', task.category);
    formData.append('status', task.status);

    return fetch('./Backend/api/update_task.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  deleteTask: function (id) {
    var formData = new FormData();
    formData.append('id', id);

    return fetch('./Backend/api/delete_task.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },
};
