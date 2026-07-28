if (document.getElementById('confirm-app')) {
  Vue.createApp({
    data: () => ({
      referenceId:  '',
      applicant:    null,
      lookupError:  '',
      confirmError: '',
      searching:    false,
      confirming:   false,
      checkedDocs:  [],
      requiredDocs: [
        'Form 137 / SHS Card',
        'Certificate of Good Moral',
        'Birth Certificate (PSA)',
        'Birth Certificate (NSO)',
        '2x2 ID Photos',
        'Form 138',
      ],
      requiredGroups: {
        'Form 137 / SHS Card': 'form_137',
        'Certificate of Good Moral': 'good_moral',
        'Birth Certificate (PSA)': 'birth_cert',
        'Birth Certificate (NSO)': 'birth_cert',
        '2x2 ID Photos': 'photo_2x2',
        'Form 138': 'form_138',
      },
      creditableSubjects: [],
      creditedSubjectIds: [],
      authorizationNote: '',
    }),
    mounted() {
      const params = new URLSearchParams(window.location.search);
      const ref = params.get('ref');
      if (ref) {
        this.referenceId = ref;
        this.search();
      }
    },
    methods: {
      async search() {
        this.lookupError  = '';
        this.confirmError = '';
        this.applicant     = null;
        this.checkedDocs   = [];

        const ref = this.referenceId.trim();
        if (!ref) return;

        this.searching = true;
        try {
            const r = await fetch(`/SIAdrafts/Backend/api/get_applicant.php?reference_id=${encodeURIComponent(ref)}`);
            const d = await r.json();
            if (!r.ok || d.error) {
            this.lookupError = d.error || 'Could not find that reference ID.';
            return;
            }
            this.applicant = d.applicant;

            if (this.applicant.applicant_type === 'Transferee') {
                try {
                    const cr = await fetch(`/SIAdrafts/Backend/api/get_creditable_subjects.php?reference_id=${encodeURIComponent(ref)}`);
                    const cd = await cr.json();
                    if (cd.error) {
                    console.warn('Could not load creditable subjects:', cd.error);
                    } else {
                    this.creditableSubjects  = cd.subjects || [];
                    this.creditedSubjectIds  = cd.already_credited || [];
                    }
                } catch (err) {
                    console.error('Creditable subjects fetch failed:', err);
                }
            }
        

        } catch (_) {
            this.lookupError = 'Connection error. Please try again.';
        } finally {
            this.searching = false;
        }
        },

      async confirm() {
        this.confirmError = '';

        const checkedGroups = this.checkedDocs.map(d => this.requiredGroups[d]);
        const allGroups = [...new Set(Object.values(this.requiredGroups))];
        const missingGroups = allGroups.filter(g => !checkedGroups.includes(g));
        const missing = missingGroups.map(g => {
          return Object.keys(this.requiredGroups).find(k => this.requiredGroups[k] === g);
        });
        if (missing.length) {
          this.confirmError = 'Missing required documents: ' + missing.join(', ') + '.';
          return;
        }

        const result = await Swal.fire({
          icon: 'question',
          title: 'Confirm this admission?',
          text: 'Double-check the applicant\u2019s guardian ID and documents before continuing.',
          showCancelButton: true,
          confirmButtonText: 'Yes, confirm',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#2f8f4e',
          cancelButtonColor: '#aaa',
          reverseButtons: true,
        });
        if (!result.isConfirmed) return;

        this.confirming = true;
        try {
          const body = new FormData();
          body.append('csrf_token', this.csrfToken());
          body.append('reference_id', this.referenceId.trim());
          this.checkedDocs.forEach(doc => body.append('docs[]', doc));
          this.creditedSubjectIds.forEach(sid => body.append('credited_subjects[]', sid));

          const r = await fetch('/SIAdrafts/Backend/api/confirm_admission.php', {
            method: 'POST',
            body,
          });
          const d = await r.json();

          if (!d.success) {
            this.confirmError = (d.errors || ['An error occurred.']).join(' ');
            return;
          }

          Swal.fire({
            icon: 'success',
            title: 'Admission confirmed',
            html: d.summary.name + ' is now verified.<br>Reference ID: <strong>' + d.summary.reference_id + '</strong>',
            confirmButtonColor: '#2f8f4e',
          });

          this.applicant.admission_status = 'verified';
        } catch (_) {
          this.confirmError = 'Connection error. Please try again.';
        } finally {
          this.confirming = false;
        }
      },

      csrfToken() {
        return document.getElementById('confirm-app').dataset.csrf || '';
      },

      async setAuthorization() {
        if (!this.authorizationNote || !this.authorizationNote.trim()) return;
        const body = new URLSearchParams({
          csrf_token: this.csrfToken(),
          applicant_id: this.applicant.applicant_id,
          action: 'set',
          note: this.authorizationNote,
        });
        const res = await fetch('/SIAdrafts/Backend/api/update_admission_authorization.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          this.applicant.authorization_note = this.authorizationNote;
          this.applicant.cleared_at = null;
          this.authorizationNote = '';
        } else {
          Swal.fire({ icon: 'error', title: 'Could not save', text: (data.errors || []).join(' ') });
        }
      },
      async clearAuthorization() {
        const body = new URLSearchParams({
          csrf_token: this.csrfToken(),
          applicant_id: this.applicant.applicant_id,
          action: 'clear',
        });
        const res = await fetch('/SIAdrafts/Backend/api/update_admission_authorization.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          this.applicant.cleared_at = new Date().toISOString();
        } else {
          Swal.fire({ icon: 'error', title: 'Could not clear', text: (data.errors || []).join(' ') });
        }
      },
      async reviewDuplicate(action) {
        const body = new URLSearchParams({
          csrf_token: this.csrfToken(),
          applicant_id: this.applicant.applicant_id,
          action: action,
        });
        const res = await fetch('/SIAdrafts/Backend/api/update_duplicate_match.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          this.applicant.duplicate_match_status = action === 'confirm' ? 'confirmed' : 'dismissed';
        } else {
          Swal.fire({ icon: 'error', title: 'Could not update', text: (data.errors || []).join(' ') });
        }
      },
    },
  }).mount('#confirm-app');
}