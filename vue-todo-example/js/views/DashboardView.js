// Plain variables and functions inside setup() — same pattern as
// StudentController's getFiltered(), called directly in the template.
// The add/edit form is just a section on the page (v-if), not a
// separate component — one less thing to learn (no props/emits).
var { ref } = Vue;

var STATUS_LABELS = {
  pending: 'Pending',
  in_progress: 'In Progress',
  completed: 'Completed',
};

var STATUS_ORDER = ['pending', 'in_progress', 'completed'];

var DashboardView = {
  setup() {
    var tasks = ref([]);
    var searchText = ref('');
    var statusFilter = ref('');
    var errorMsg = ref('');

    var showForm = ref(false);
    var editingId = ref(null);
    var formTitle = ref('');
    var formNotes = ref('');
    var formCategory = ref('');
    var formStatus = ref('pending');

    function loadTasks() {
      TaskController.getTasks().then(function (data) {
        tasks.value = data.tasks || [];
      });
    }

    loadTasks();

    function filteredTasks() {
      var lowerSearch = searchText.value.toLowerCase();
      return tasks.value.filter(function (t) {
        var matchesStatus = !statusFilter.value || t.status === statusFilter.value;
        var matchesSearch = !lowerSearch || t.title.toLowerCase().indexOf(lowerSearch) !== -1;
        return matchesStatus && matchesSearch;
      });
    }

    function countByStatus(status) {
      return tasks.value.filter(function (t) { return t.status === status; }).length;
    }

    function statusLabel(status) {
      return STATUS_LABELS[status];
    }

    function cycleStatus(task) {
      var currentIndex = STATUS_ORDER.indexOf(task.status);
      var nextStatus = STATUS_ORDER[(currentIndex + 1) % STATUS_ORDER.length];

      TaskController.updateTask({
        id: task.id,
        title: task.title,
        notes: task.notes,
        category: task.category,
        status: nextStatus,
      }).then(function (data) {
        if (data.success) {
          task.status = nextStatus;
        } else {
          errorMsg.value = data.error;
        }
      });
    }

    function openAddForm() {
      editingId.value = null;
      formTitle.value = '';
      formNotes.value = '';
      formCategory.value = '';
      formStatus.value = 'pending';
      showForm.value = true;
    }

    function openEditForm(task) {
      editingId.value = task.id;
      formTitle.value = task.title;
      formNotes.value = task.notes;
      formCategory.value = task.category;
      formStatus.value = task.status;
      showForm.value = true;
    }

    function closeForm() {
      showForm.value = false;
    }

    function saveTask() {
      var payload = {
        id: editingId.value,
        title: formTitle.value,
        notes: formNotes.value,
        category: formCategory.value,
        status: formStatus.value,
      };

      var request = editingId.value ? TaskController.updateTask(payload) : TaskController.createTask(payload);

      request.then(function (data) {
        if (data.success) {
          loadTasks();
          closeForm();
        } else {
          errorMsg.value = data.error;
        }
      });
    }

    function removeTask(task) {
      if (!window.confirm('Delete "' + task.title + '"?')) {
        return;
      }

      TaskController.deleteTask(task.id).then(function (data) {
        if (data.success) {
          tasks.value = tasks.value.filter(function (t) { return t.id !== task.id; });
        } else {
          errorMsg.value = data.error;
        }
      });
    }

    function logout() {
      AuthController.logout().then(function () {
        window.location.hash = '#/login';
      });
    }

    return {
      tasks,
      searchText,
      statusFilter,
      errorMsg,
      showForm,
      editingId,
      formTitle,
      formNotes,
      formCategory,
      formStatus,
      filteredTasks,
      countByStatus,
      statusLabel,
      cycleStatus,
      openAddForm,
      openEditForm,
      closeForm,
      saveTask,
      removeTask,
      logout,
    };
  },
  template: `
    <div class="page">
      <div class="topbar">
        <h1>My Tasks</h1>
        <nav>
          <a href="#" @click.prevent="logout">Log Out</a>
        </nav>
      </div>

      <div class="cards">
        <div class="stat-card">
          <div class="stat-value">{{ tasks.length }}</div>
          <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ countByStatus('pending') }}</div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ countByStatus('in_progress') }}</div>
          <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ countByStatus('completed') }}</div>
          <div class="stat-label">Completed</div>
        </div>
      </div>

      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>

      <div class="toolbar">
        <input v-model="searchText" class="search-box" placeholder="Search tasks..." />
        <button @click="openAddForm">+ Add Task</button>
      </div>

      <div class="filter-bar">
        <button @click="statusFilter = ''" :class="{ active: statusFilter === '' }">All</button>
        <button @click="statusFilter = 'pending'" :class="{ active: statusFilter === 'pending' }">Pending</button>
        <button @click="statusFilter = 'in_progress'" :class="{ active: statusFilter === 'in_progress' }">In Progress</button>
        <button @click="statusFilter = 'completed'" :class="{ active: statusFilter === 'completed' }">Completed</button>
      </div>

      <div class="task-form" v-if="showForm">
        <h2>{{ editingId ? 'Edit Task' : 'Add Task' }}</h2>
        <form @submit.prevent="saveTask">
          <input v-model="formTitle" placeholder="Title" required />
          <textarea v-model="formNotes" placeholder="Notes"></textarea>
          <input v-model="formCategory" placeholder="Subject / Category" />
          <select v-model="formStatus">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
          <div>
            <button type="submit">Save</button>
            <button type="button" class="secondary" @click="closeForm">Cancel</button>
          </div>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in filteredTasks()" :key="t.id">
            <td>
              <strong>{{ t.title }}</strong>
              <div v-if="t.notes">{{ t.notes }}</div>
            </td>
            <td>{{ t.category }}</td>
            <td>
              <span class="status-badge" :class="'status-' + t.status" @click="cycleStatus(t)">
                {{ statusLabel(t.status) }}
              </span>
            </td>
            <td>
              <button class="secondary" @click="openEditForm(t)">Edit</button>
              <button class="secondary" @click="removeTask(t)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="filteredTasks().length === 0">No tasks found.</p>
    </div>
  `,
};
