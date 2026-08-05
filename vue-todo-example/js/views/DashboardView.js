var { ref, computed } = Vue;

var STATUS_LABELS = {
  pending: 'Pending',
  in_progress: 'In Progress',
  completed: 'Completed',
};

var STATUS_ORDER = ['pending', 'in_progress', 'completed'];

var DashboardView = {
  components: { TaskModal: TaskModal },
  setup() {
    var tasks = ref([]);
    var searchText = ref('');
    var statusFilter = ref('');
    var showModal = ref(false);
    var editingTask = ref(null);
    var errorMsg = ref('');

    function loadTasks() {
      TaskController.getTasks().then(function (data) {
        tasks.value = data.tasks || [];
      });
    }

    loadTasks();

    var filteredTasks = computed(function () {
      var lowerSearch = searchText.value.toLowerCase();
      return tasks.value.filter(function (t) {
        var matchesStatus = !statusFilter.value || t.status === statusFilter.value;
        var matchesSearch = !lowerSearch ||
          t.title.toLowerCase().indexOf(lowerSearch) !== -1 ||
          (t.notes || '').toLowerCase().indexOf(lowerSearch) !== -1;
        return matchesStatus && matchesSearch;
      });
    });

    var totalCount = computed(function () { return tasks.value.length; });
    var pendingCount = computed(function () {
      return tasks.value.filter(function (t) { return t.status === 'pending'; }).length;
    });
    var inProgressCount = computed(function () {
      return tasks.value.filter(function (t) { return t.status === 'in_progress'; }).length;
    });
    var completedCount = computed(function () {
      return tasks.value.filter(function (t) { return t.status === 'completed'; }).length;
    });

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
          errorMsg.value = '';
        } else {
          errorMsg.value = data.error;
        }
      });
    }

    function openAddModal() {
      editingTask.value = null;
      showModal.value = true;
    }

    function openEditModal(task) {
      editingTask.value = task;
      showModal.value = true;
    }

    function closeModal() {
      showModal.value = false;
      editingTask.value = null;
    }

    function saveTask(payload) {
      var request = payload.id ? TaskController.updateTask(payload) : TaskController.createTask(payload);

      request.then(function (data) {
        if (data.success) {
          errorMsg.value = '';
          loadTasks();
          closeModal();
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
          errorMsg.value = '';
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
      showModal,
      editingTask,
      errorMsg,
      filteredTasks,
      totalCount,
      pendingCount,
      inProgressCount,
      completedCount,
      statusLabel,
      cycleStatus,
      openAddModal,
      openEditModal,
      closeModal,
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
          <div class="stat-value">{{ totalCount }}</div>
          <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ pendingCount }}</div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ inProgressCount }}</div>
          <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ completedCount }}</div>
          <div class="stat-label">Completed</div>
        </div>
      </div>

      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>

      <div class="toolbar">
        <input v-model="searchText" class="search-box" placeholder="Search tasks..." />
        <button @click="openAddModal">+ Add Task</button>
      </div>

      <div class="filter-bar">
        <button @click="statusFilter = ''" :class="{ active: statusFilter === '' }">All</button>
        <button @click="statusFilter = 'pending'" :class="{ active: statusFilter === 'pending' }">Pending</button>
        <button @click="statusFilter = 'in_progress'" :class="{ active: statusFilter === 'in_progress' }">In Progress</button>
        <button @click="statusFilter = 'completed'" :class="{ active: statusFilter === 'completed' }">Completed</button>
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
          <tr v-for="t in filteredTasks" :key="t.id">
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
              <button class="secondary" @click="openEditModal(t)">Edit</button>
              <button class="secondary" @click="removeTask(t)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="filteredTasks.length === 0">No tasks found.</p>

      <task-modal v-if="showModal" :task="editingTask" @save="saveTask" @cancel="closeModal"></task-modal>
    </div>
  `,
};
