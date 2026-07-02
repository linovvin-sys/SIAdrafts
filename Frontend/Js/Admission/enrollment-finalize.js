Vue.createApp({
  data: () => ({
    saving: false,
    error:  null,
  }),
  methods: {
    async finalize() {
      this.error = null;
      this.saving = true;
      try {
        const r = await fetch('/SIAdrafts/Backend/api/save_enrollment.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({
            student_id:  ENROLLMENT_PAYLOAD.student_id,
            school_year: ENROLLMENT_PAYLOAD.school_year,
            semester:    ENROLLMENT_PAYLOAD.semester,
            year_level:  ENROLLMENT_PAYLOAD.year_level,
            type_id:     ENROLLMENT_PAYLOAD.type_id,
            section_id:  ENROLLMENT_PAYLOAD.section_id,
            subject_ids: ENROLLMENT_PAYLOAD.subject_ids,
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