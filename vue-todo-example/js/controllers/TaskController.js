var TaskController = {
  getTasks() {
    return fetch('./Backend/api/tasks.php?action=list').then(function (response) {
      return response.json();
    });
  },

  createTask(task) {
    var formData = new FormData();
    formData.append('action', 'create');
    formData.append('title', task.title);
    formData.append('notes', task.notes);
    formData.append('category', task.category);
    formData.append('status', task.status);

    return fetch('./Backend/api/tasks.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  updateTask(task) {
    var formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', task.id);
    formData.append('title', task.title);
    formData.append('notes', task.notes);
    formData.append('category', task.category);
    formData.append('status', task.status);

    return fetch('./Backend/api/tasks.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  deleteTask(id) {
    var formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    return fetch('./Backend/api/tasks.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },
};
