Vue.createApp({
  data: () => ({
    categories:  [],
    selected:    [],
    loading:     true,
    loadError:   null,
    submitError: null,
  }),
  computed: {
    totalUnits() {
      let sum = 0;
      this.categories.forEach(cat => {
        cat.subjects.forEach(s => {
          if (this.selected.includes(s.subject_id)) sum += parseFloat(s.units);
        });
      });
      return sum.toFixed(2);
    },
    allSubjects() {
      return this.categories.flatMap(c => c.subjects);
    },
  },
  methods: {
    catAllChecked(cat) {
      return cat.subjects.length > 0
        && cat.subjects.every(s => this.selected.includes(s.subject_id));
    },
    catSomeChecked(cat) {
      return cat.subjects.some(s => this.selected.includes(s.subject_id))
        && !this.catAllChecked(cat);
    },
    toggleCategory(cat) {
      if (this.catAllChecked(cat)) {
        this.selected = this.selected.filter(
          id => !cat.subjects.some(s => s.subject_id === id)
        );
      } else {
        cat.subjects.forEach(s => {
          if (!this.selected.includes(s.subject_id)) this.selected.push(s.subject_id);
        });
      }
    },
    selectAll()  { this.selected = this.allSubjects.map(s => s.subject_id); },
    clearAll()   { this.selected = []; },

    fmtTime(t) {
      if (!t) return '';
      const [h, m] = t.split(':');
      const hr = parseInt(h);
      return (hr > 12 ? hr - 12 : hr || 12) + ':' + m + (hr >= 12 ? 'PM' : 'AM');
    },

    proceed() {
      if (this.selected.length === 0) {
        this.submitError = 'Please select at least one subject.';
        return;
      }
      this.submitError = null;
      document.getElementById('subj-form').submit();
    },

    async loadSubjects() {
      const params = new URLSearchParams({
        year_level:  ENROLL_META.year_level,
        semester:    ENROLL_META.semester,
        school_year: ENROLL_META.school_year,
        section_id:  ENROLL_META.section_id,
      });
      try {
        const r = await fetch('api/get_subjects.php?' + params);
        if (!r.ok) throw new Error('Server error ' + r.status);
        const d = await r.json();
        if (d.error) throw new Error(d.error);
        this.categories = d.categories || [];
        if (ENROLL_META.auto_types.includes(ENROLL_META.type_id)) {
          this.selected = this.allSubjects.map(s => s.subject_id);
        }
      } catch (e) {
        this.loadError = e.message || 'Failed to load subjects.';
      } finally {
        this.loading = false;
      }
    },
  },
  mounted() { this.loadSubjects(); },
}).mount('#subject-app');
