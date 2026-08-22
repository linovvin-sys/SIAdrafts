Vue.createApp({
  data: () => ({
    saving: false,
    saved:  false,
    error:  null,
    enrollmentRef:  null,
    studentIdLabel: 'Reference ID',
    studentIdValue: ENROLLMENT_PAYLOAD.reference_id,
  }),
  methods: {
    async finalize() {
      const ok = await (window.confirmAction
        ? window.confirmAction({
            title: 'Finalize this enrollment?',
            text: 'This will save the enrollment and generate the student\u2019s billing. This cannot be undone from here.',
            icon: 'question',
            confirmText: 'Yes, finalize',
          })
        : Promise.resolve(window.confirm('Finalize this enrollment?')));
      if (!ok) return;

      this.error = null;
      this.saving = true;
      try {
        const r = await fetch('/SIAdrafts/Backend/api/Enrollment/save_enrollment.php', {
          method:  'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.body.dataset.csrf || '',
          },
          body:    JSON.stringify({
            student_id:  ENROLLMENT_PAYLOAD.student_id,
            school_year: ENROLLMENT_PAYLOAD.school_year,
            semester:    ENROLLMENT_PAYLOAD.semester,
            year_level:  ENROLLMENT_PAYLOAD.year_level,
            type_id:     ENROLLMENT_PAYLOAD.type_id,
            section_id:  ENROLLMENT_PAYLOAD.section_id,
            subject_ids: ENROLLMENT_PAYLOAD.subject_ids,
            schedule_ids: ENROLLMENT_PAYLOAD.schedule_ids,
          }),
        });
        const d = await r.json();
        if (d.success) {
          this.studentIdLabel = 'Student ID';
          this.studentIdValue = d.student_no;
          this.enrollmentRef  = d.enrollment_id;
          this.saved  = true;
          this.saving = false;
          if (window.confirmAction) {
            await window.confirmAction({
              title: 'Enrollment saved',
              text: 'Student number: ' + d.student_no + '. Print the registration form now for the student to bring to Treasury.',
              icon: 'success',
              confirmText: 'Continue',
              showCancelButton: false,
            });
          }
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