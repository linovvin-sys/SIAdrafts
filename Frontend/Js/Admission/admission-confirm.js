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
        '2x2 ID Photos',
      ],
      creditableSubjects: [],
      creditedSubjectIds: [],
    }),
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

        const missing = this.requiredDocs.filter(d => !this.checkedDocs.includes(d));
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
    },
  }).mount('#confirm-app');
}