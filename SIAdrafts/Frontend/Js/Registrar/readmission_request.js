Vue.createApp({
  data: () => ({
    studentNo:    '',
    student:      null,
    lookupError:  '',
    looking:      false,

    reason:       '',
    schoolYear:   '',
    semester:     '1',
    isShifting:   false,
    newCourseId:  '',

    submitError:  '',
    submitting:   false,
    submitted:    false,
  }),
  methods: {
    async lookup() {
      this.lookupError = '';
      this.student      = null;
      const sid = this.studentNo.trim();
      if (!sid) return;

      this.looking = true;
      try {
        const r = await fetch(`/SIAdrafts/Backend/api/Enrollment/lookup_student.php?student_no=${encodeURIComponent(sid)}`);
        const d = await r.json();
        if (d.error) {
          this.lookupError = d.error;
        } else {
          this.student = d.student;
        }
      } catch (_) {
        this.lookupError = 'Connection error. Please try again.';
      } finally {
        this.looking = false;
      }
    },

    async submit() {
      this.submitError = '';

      if (!this.reason.trim() || !this.schoolYear.trim() || !this.semester) {
        this.submitError = 'Please fill out all required fields.';
        return;
      }
      if (this.isShifting && !this.newCourseId) {
        this.submitError = 'Select the program being shifted into.';
        return;
      }

      this.submitting = true;
      const body = new FormData();
      body.append('csrf_token', document.body.dataset.csrf || '');
      body.append('student_no', this.student.student_no);
      body.append('reason', this.reason.trim());
      body.append('school_year', this.schoolYear.trim());
      body.append('semester', this.semester);
      body.append('is_shifting', this.isShifting ? '1' : '');
      if (this.isShifting) body.append('new_course_id', this.newCourseId);

      try {
        const r = await fetch('/SIAdrafts/Backend/api/Enrollment/submit_readmission.php', { method: 'POST', body });
        const d = await r.json();
        if (d.success) {
          this.submitted = true;
        } else {
          this.submitError = d.error || 'An error occurred.';
        }
      } catch (_) {
        this.submitError = 'Connection error. Please try again.';
      } finally {
        this.submitting = false;
      }
    },
  },
}).mount('#readmit-app');