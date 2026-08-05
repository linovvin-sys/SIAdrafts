// Plain variables and a function inside setup().
// Fields start from `task` once — the parent uses a :key so Vue
// makes a fresh copy of this component whenever `task` changes.
var { ref } = Vue;

var TaskModal = {
  props: ['task'],
  emits: ['save', 'cancel'],
  setup(props, context) {
    var title = ref(props.task ? props.task.title : '');
    var notes = ref(props.task ? props.task.notes : '');
    var category = ref(props.task ? props.task.category : '');
    var status = ref(props.task ? props.task.status : 'pending');

    function submit() {
      context.emit('save', {
        id: props.task ? props.task.id : undefined,
        title: title.value,
        notes: notes.value,
        category: category.value,
        status: status.value,
      });
    }

    function cancel() {
      context.emit('cancel');
    }

    return { title, notes, category, status, submit, cancel };
  },
  template: `
    <div class="modal-backdrop" @click.self="cancel">
      <div class="modal">
        <h2>{{ task ? 'Edit Task' : 'Add Task' }}</h2>
        <form @submit.prevent="submit">
          <input v-model="title" placeholder="Title" required />
          <textarea v-model="notes" placeholder="Notes"></textarea>
          <input v-model="category" placeholder="Subject / Category" />
          <select v-model="status">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
          <div class="modal-actions">
            <button type="button" class="secondary" @click="cancel">Cancel</button>
            <button type="submit">Save</button>
          </div>
        </form>
      </div>
    </div>
  `,
};
