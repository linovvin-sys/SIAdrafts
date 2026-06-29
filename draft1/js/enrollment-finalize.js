Vue.createApp({
  data: () => ({
    amount_due:  '',
    downpayment: '0',
    due_date:    ENROLLMENT_PAYLOAD.default_due,
    saving:      false,
    error:       null,
  }),
  computed: {
    balance() {
      const bal = parseFloat(this.amount_due || 0) - parseFloat(this.downpayment || 0);
      return Math.max(0, bal).toFixed(2);
    },
  },
  methods: {
    async finalize() {
      this.error = null;
      if (!this.amount_due || parseFloat(this.amount_due) < 0) {
        this.error = 'Please enter a valid amount due.'; return;
      }
      if (!this.due_date) {
        this.error = 'Please set a payment due date.'; return;
      }
      this.saving = true;
      try {
        const r = await fetch('api/save_enrollment.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({
            student_id:  ENROLLMENT_PAYLOAD.student_id,
            school_year: ENROLLMENT_PAYLOAD.school_year,
            semester:    ENROLLMENT_PAYLOAD.semester,
            year_level:  ENROLLMENT_PAYLOAD.year_level,
            type_id:     ENROLLMENT_PAYLOAD.type_id,
            subject_ids: ENROLLMENT_PAYLOAD.subject_ids,
            amount_due:  parseFloat(this.amount_due),
            downpayment: parseFloat(this.downpayment || 0),
            due_date:    this.due_date,
          }),
        });
        const d = await r.json();
        if (d.success) {
          window.location.href = 'enrollment.php?enrolled=1&ref=' + d.enrollment_id;
        } else {
          this.error = d.error || 'An error occurred.';
          this.saving = false;
        }
      } catch (_) {
        this.error = 'Connection error. Please try again.';
        this.saving = false;
      }
    },
  },
}).mount('#confirm-app');
