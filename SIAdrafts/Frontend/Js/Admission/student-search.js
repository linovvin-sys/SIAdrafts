if (document.getElementById('enroll-app')) {
  Vue.createApp({
    data: () => ({
      nameMode:  false,
      query:     '',
      results:   [],
      searching: false,
      noResults: false,
      _timer:    null,
    }),
    computed: {
      hint: vm => vm.nameMode
        ? 'Enter first or last name (e.g. Juan Dela Cruz)'
        : 'Format: YYYY-NNNNN (e.g. 2025-00001)',
    },
    methods: {
      toggle() {
        this.nameMode  = !this.nameMode;
        this.query     = '';
        this.results   = [];
        this.noResults = false;
      },

      onInput() {
        if (!this.nameMode) {
          let digits = this.query.replace(/\D/g, '').slice(0, 9);
          this.query = digits.length > 4 ? digits.slice(0, 4) + '-' + digits.slice(4) : digits;
        }
        this.noResults = false;
        clearTimeout(this._timer);
        if (this.query.length < 2) { this.results = []; return; }
        this._timer = setTimeout(() => this.doSearch(), 380);
      },

      async doSearch() {
        this.searching = true;
        this.results   = [];
        const mode = this.nameMode ? 'name' : 'id';
        try {
      const r = await fetch(
          `/SIAdrafts/Backend/api/get_student.php?q=${encodeURIComponent(this.query)}&mode=${mode}`
      );
          const d = await r.json();
          this.results   = Array.isArray(d) ? d : [];
          this.noResults = this.results.length === 0;
        } catch (_) {
          this.results = [];
        } finally {
          this.searching = false;
        }
      },

      pick(student) {
          window.location.href = `/SIAdrafts/Frontend/View/Admission/enrollment_profile.php?student_id=${student.student_id}`;
      },

      submitSearch() {
          if (!this.query.trim()) return;
          if (this.results.length === 1) { this.pick(this.results[0]); return; }
          const mode = this.nameMode ? 'name' : 'id';
          window.location.href =
              `/SIAdrafts/Frontend/View/Admission/enrollment_profile.php?q=${encodeURIComponent(this.query)}&mode=${mode}`;
      },
    },
  }).mount('#enroll-app');
}
