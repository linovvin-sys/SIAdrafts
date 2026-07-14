<?php
$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/admission-confirm.js'];
require_once '../../../Backend/auth.php';

include '../Admission/Include/header.php';
?>

<div class="admission-page">

  <div class="admission-head">
    <span class="admission-eyebrow">
      <iconify-icon icon="mdi:school"></iconify-icon>
      Walk-in Admission — Document Verification
    </span>
    <h1>Confirm an Applicant</h1>
    <p>Ask the applicant for their reference ID (given after their online application), pull up their record, and check off the physical documents they hand over today.</p>
  </div>

  <div id="confirm-app" class="admission-card" style="padding:32px;">

    <div class="row g-3 align-items-end mb-3">
      <div class="col-md-8">
        <label class="form-label">Reference ID</label>
        <input
          class="form-control"
          v-model="referenceId"
          placeholder="e.g. REF-00042-007"
          @keydown.enter.prevent="search"
          autocomplete="off">
      </div>
      <div class="col-md-4">
        <button type="button" class="btn btn-submit w-100" @click="search" :disabled="searching">
          {{ searching ? 'Searching…' : 'Look up' }}
        </button>
      </div>
    </div>

    <div v-if="lookupError" class="alert-box alert-error mb-3">{{ lookupError }}</div>

    <div v-if="applicant">

      <div class="form-section">
        <div class="section-head">
          <span class="section-num"><iconify-icon icon="mdi:account"></iconify-icon></span>
          <div>
            <h2>{{ applicant.full_name }}</h2>
            <p>{{ applicant.program }} &middot; Year {{ applicant.year_level }} &middot; {{ applicant.start_term }}</p>
          </div>
        </div>
        <div class="row g-2">
          <div class="col-md-6"><strong>Contact:</strong> {{ applicant.contact_number }}</div>
          <div class="col-md-6"><strong>Birth date:</strong> {{ applicant.birth_date }}</div>
          <div class="col-12"><strong>Address:</strong> {{ applicant.home_address }}</div>
          <div class="col-md-6"><strong>Guardian:</strong> {{ applicant.guardian_name }} ({{ applicant.guardian_relationship }})</div>
          <div class="col-md-6"><strong>Guardian contact:</strong> {{ applicant.guardian_contact }}</div>
          <div class="col-md-6"><strong>Guardian ID:</strong> {{ applicant.guardian_id_type }} — {{ applicant.guardian_id_number }}</div>
          <div class="col-md-6"><strong>Status:</strong> {{ applicant.admission_status }}</div>
        </div>
      </div>

      <div v-if="applicant.admission_status === 'verified'" class="alert-box alert-success mb-3">
        This reference ID was already verified on {{ applicant.verified_at }}.
      </div>

      <div v-else class="form-section">
        <div class="section-head">
          <span class="section-num">✓</span>
          <div>
            <h2>Document Checklist</h2>
            <p>Check off only what the applicant has physically handed over today.</p>
          </div>
        </div>

        <div class="doc-checklist">
          <div class="doc-item" v-for="doc in requiredDocs" :key="doc">
            <input type="checkbox" :id="doc" v-model="checkedDocs" :value="doc">
            <label :for="doc">{{ doc }}</label>
            <span class="req">Required</span>
          </div>
        </div>

        <div class="form-section" v-if="applicant.applicant_type === 'Transferee' && creditableSubjects.length">
          <div class="section-head">
            <span class="section-num"><iconify-icon icon="mdi:file-check-outline"></iconify-icon></span>
            <div>
              <h2>Subject Credits</h2>
              <p>Check off any subjects already satisfied at the applicant's previous school, based on their Transcript of Records.</p>
            </div>
          </div>

          <div class="subject-credit-list">
            <div class="doc-item" v-for="sub in creditableSubjects" :key="sub.subject_id">
              <input type="checkbox" :id="'cr_'+sub.subject_id" v-model="creditedSubjectIds" :value="sub.subject_id">
              <label :for="'cr_'+sub.subject_id">{{ sub.subject_code }} — {{ sub.subject_name }} ({{ sub.year_level }}Y, Sem {{ sub.semester }})</label>
            </div>
          </div>
        </div>

        <div v-if="confirmError" class="alert-box alert-error mt-3">{{ confirmError }}</div>

        <div class="form-actions">
          <span class="hint">This finalizes verification — it can't be undone from here.</span>
          <button type="button" class="btn btn-submit" @click="confirm" :disabled="confirming">
            {{ confirming ? 'Confirming…' : 'Confirm admission' }}
          </button>
        </div>
      </div>

    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include '../Admission/Include/footer.php' ?>