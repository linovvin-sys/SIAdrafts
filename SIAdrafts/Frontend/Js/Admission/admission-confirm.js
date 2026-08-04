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
      laterDocs:    [],
      requiredDocs: [
        'Certificate of Good Moral',
        'Birth Certificate (PSA)',
        'Birth Certificate (NSO)',
        '2x2 ID Photos',
        'Form 138',
      ],
      requiredGroups: {
        'Certificate of Good Moral': 'good_moral',
        'Birth Certificate (PSA)': 'birth_cert',
        'Birth Certificate (NSO)': 'birth_cert',
        '2x2 ID Photos': 'photo_2x2',
        'Form 138': 'form_138',
      },
      // PSA/NSO and Good Moral must be physically in hand to confirm — the
      // rest can be marked "to follow" and chased up later instead of
      // blocking the walk-in confirmation outright.
      criticalGroups: ['birth_cert', 'good_moral'],
      creditableSubjects: [],
      creditedSubjectIds: [],
      authorizationNote: '',
      onlineRequirements: [],
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
        this.laterDocs     = [];
        this.onlineRequirements = [];

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
            this.onlineRequirements = d.applicant.online_requirements || [];

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

      isCritical(doc) {
        return this.criticalGroups.includes(this.requiredGroups[doc]);
      },

      // Matches a checklist label (e.g. "Birth Certificate (PSA)") back to
      // whatever the applicant submitted online for it, if anything — a
      // soft copy to view, or a note that they said they'd bring it in.
      onlineRecordFor(doc) {
        return this.onlineRequirements.find(r => r.document_name === doc) || null;
      },
      documentViewUrl(record) {
        return `/SIAdrafts/Backend/api/view_requirement_document.php?document_id=${record.document_id}`;
      },

      // Submitted and "to follow" are mutually exclusive per document.
      toggleLater(doc) {
        const i = this.checkedDocs.indexOf(doc);
        if (i !== -1) this.checkedDocs.splice(i, 1);
        const j = this.laterDocs.indexOf(doc);
        if (j === -1) this.laterDocs.push(doc);
        else this.laterDocs.splice(j, 1);
      },

      async confirm() {
        this.confirmError = '';

        const accountedGroups = [...this.checkedDocs, ...this.laterDocs].map(d => this.requiredGroups[d]);
        const missingGroups = this.criticalGroups.filter(g => !accountedGroups.includes(g));
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
          this.laterDocs.forEach(doc => body.append('docs_later[]', doc));
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

          const laterNote = (d.summary.documents_later || []).length
            ? '<br><br>Still to follow: ' + d.summary.documents_later.join(', ') + '.'
            : '';
          Swal.fire({
            icon: 'success',
            title: 'Admission confirmed',
            html: d.summary.name + ' is now verified.<br>Reference ID: <strong>' + d.summary.reference_id + '</strong>' + laterNote,
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