if (document.getElementById('irregular-app')) {
  Vue.createApp({
    data: () => ({
      loading:         true,
      loadError:       null,
      categories:      [],
      picks:           {},   // { [subject_id]: schedule_id }
      submitError:     null,
      conflictWarning: null,
    }),
    computed: {
      allSubjects() {
        return this.categories.flatMap(c => c.subjects);
      },
      allOptions() {
        // flatten every subject's options with a back-reference, for conflict lookups
        const out = [];
        this.allSubjects.forEach(sub => {
          sub.options.forEach(opt => out.push({ subject_id: sub.subject_id, ...opt }));
        });
        return out;
      },
      selectedOptions() {
        return Object.entries(this.picks)
          .filter(([, scheduleId]) => scheduleId)
          .map(([subjectId, scheduleId]) =>
            this.allOptions.find(o => o.subject_id == subjectId && o.schedule_id === scheduleId)
          )
          .filter(Boolean);
      },
      hasAnyPick() {
        return this.selectedOptions.length > 0;
      },
      totalUnits() {
        let sum = 0;
        this.allSubjects.forEach(sub => {
          if (this.picks[sub.subject_id]) sum += parseFloat(sub.units);
        });
        return sum.toFixed(2);
      },
      picksJson() {
        return JSON.stringify(
          Object.entries(this.picks)
            .filter(([, scheduleId]) => scheduleId)
            .map(([subject_id, schedule_id]) => ({ subject_id: Number(subject_id), schedule_id }))
        );
      },
    },
    methods: {
      timesOverlap(a, b) {
        return a.day === b.day && a.time_start < b.time_end && b.time_start < a.time_end;
      },

      isConflicted(subjectId, opt) {
        // an option is "conflicted" if it overlaps a DIFFERENT subject's current pick
        return this.selectedOptions.some(sel =>
          sel.subject_id != subjectId && this.timesOverlap(sel, opt)
        );
      },

      onPick(subjectId) {
        this.conflictWarning = null;
        const chosen = this.selectedOptions.find(o => o.subject_id == subjectId);
        if (!chosen) return;
        const clash = this.selectedOptions.find(o =>
          o.subject_id != subjectId && this.timesOverlap(o, chosen)
        );
        if (clash) {
          this.conflictWarning = `This schedule overlaps with a subject you already picked (${clash.day} ${this.fmtTime(clash.time_start)}\u2013${this.fmtTime(clash.time_end)}). Choose a different slot.`;
          this.picks[subjectId] = null; // revert the pick
        }
      },

      clearPick(subjectId) {
        this.picks[subjectId] = null;
        this.conflictWarning = null;
      },

      fmtTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':').map(Number);
        const period = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 === 0 ? 12 : h % 12;
        return `${h12}:${String(m).padStart(2, '0')} ${period}`;
      },

      proceed() {
        if (!this.hasAnyPick) return;
        document.getElementById('irregular-form').submit();
      },

      async fetchSubjects() {
        this.loading   = true;
        this.loadError = null;
        try {
          const params = new URLSearchParams({
            year_level:  ENROLL_META.year_level,
            semester:    ENROLL_META.semester,
            school_year: ENROLL_META.school_year,
            course_id:   ENROLL_META.course_id,
          });
          const r = await fetch(`/SIAdrafts/Backend/api/Scheduling/get_available_schedules.php?${params}`);
          const d = await r.json();
          if (d.error) {
            this.loadError = d.error;
          } else {
            this.categories = d.categories || [];
            this.allSubjects.forEach(sub => { this.picks[sub.subject_id] = null; });
          }
        } catch (_) {
          this.loadError = 'Could not load subjects. Please try again.';
        } finally {
          this.loading = false;
        }
      },
    },
    mounted() {
      this.fetchSubjects();
    },
  }).mount('#irregular-app');
}