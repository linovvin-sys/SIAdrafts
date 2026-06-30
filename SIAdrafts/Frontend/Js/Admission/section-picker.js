// Section picker page Vue app (enrollment_subjects.php)
if (document.getElementById('section-app')) {
  Vue.createApp({
    data: () => ({
      loading:     true,
      loadError:   null,
      sections:    [],
      selectedId:  null,
      submitting:  false,
      submitError: null,
    }),
    mounted() {
      this.fetchSections();
    },
    methods: {
      async fetchSections() {
        this.loading   = true;
        this.loadError = null;
        try {
          const params = new URLSearchParams({
            year_level:  ENROLL_META.year_level,
            semester:    ENROLL_META.semester,
            school_year: ENROLL_META.school_year,
          });
          const r = await fetch(`/SIAdrafts/Backend/api/get_sections.php?${params}`);
          const d = await r.json();
          if (d.error) {
            this.loadError = d.error;
          } else {
            this.sections = d.sections || [];
          }
        } catch (_) {
          this.loadError = 'Could not load sections. Please try again.';
        } finally {
          this.loading = false;
        }
      },

      select(id) {
        this.selectedId  = id;
        this.submitError = null;
      },

      fmtTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':').map(Number);
        const period = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 === 0 ? 12 : h % 12;
        return `${h12}:${String(m).padStart(2, '0')} ${period}`;
      },

      proceed() {
        if (!this.selectedId) return;
        this.submitting = true;
        document.getElementById('sec-form').submit();
      },
    },
  }).mount('#section-app');
}