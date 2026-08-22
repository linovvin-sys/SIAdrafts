<?php
$pageTitle  = "ADMISSION";
$activePage = "admission";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_ADMIN]);
require_once '../../../Backend/csrf.php';
$csrfToken = csrf_token();

// This page uses the shared Include/header.php (app-layout/page-content
// shell), not Admission's own header, so admission.css — which defines
// .admission-card, .form-section, .doc-checklist, etc. — was never being
// loaded and the whole page rendered unstyled. Same fix as enrollment.php.
// style.css must come first: admission.css's rules (e.g. .btn-submit's
// background/color) reference --ink/--paper/--sage, which are only
// defined in style.css's :root block, not in admission.css itself.
$extraCss = [
    '/SIAdrafts/Frontend/Css/Admission/style.css',
    '/SIAdrafts/Frontend/Css/Admission/admission.css',
];

include '../Include/header.php';
?>

<!-- The shared header doesn't load this (only Admission's own header does),
     so every <iconify-icon> on this page — including the ones just added to
     the soft-copy preview modal — rendered as nothing without it. -->
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>

<div class="app-layout">

<?php include '../Include/sidebar.php'; ?>

<main class="page-content">

<div class="admission-page">

  <div class="admission-head">
    <span class="admission-eyebrow">
      <iconify-icon icon="mdi:school"></iconify-icon>
      Walk-in Admission — Document Verification
    </span>
    <h1>Confirm an Applicant</h1>
    <p>Ask the applicant for their reference ID (given after their online application), pull up their record, and check off the physical documents they hand over today.</p>
  </div>

  <div id="confirm-app" class="admission-card" style="padding:32px;" data-csrf="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

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

    <div v-if="isReadonly" class="alert-box alert-info mb-3">
      Read-only view — Admin can look up and review applicants here, but document verification and confirmation must be done by Admission staff.
    </div>

    <div v-if="applicant">

      <div class="form-section">
        <div class="section-head">
          <span class="section-num"><iconify-icon icon="mdi:account"></iconify-icon></span>
          <div>
            <h2>{{ applicant.full_name }}</h2>
            <p>{{ applicant.program }} &middot; Year {{ applicant.year_level }} &middot; {{ applicant.school_year }} Sem {{ applicant.semester }}</p>
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

      <div v-if="applicant.duplicate_match_status === 'pending_review'" class="alert-box alert-warning mb-3">
        Possible returning student — a matching record was found.
        <button type="button" class="btn btn-outline" style="margin-left:8px;" :disabled="isReadonly" @click="reviewDuplicate('confirm')">Confirm match</button>
        <button type="button" class="btn btn-outline" style="margin-left:8px;" :disabled="isReadonly" @click="reviewDuplicate('dismiss')">Dismiss</button>
      </div>

      <div class="form-section">
        <div class="section-head">
          <span class="section-num"><iconify-icon icon="mdi:shield-check-outline"></iconify-icon></span>
          <div>
            <h2>Admission Authorization</h2>
            <p>Optional staff note authorizing this applicant to proceed, if applicable.</p>
          </div>
        </div>
        <div v-if="applicant.authorization_note && !applicant.cleared_at" class="alert-box alert-info mb-2">
          {{ applicant.authorization_note }}
          <button type="button" class="btn btn-outline" style="margin-left:8px;" :disabled="isReadonly" @click="clearAuthorization">Clear</button>
        </div>
        <div v-else class="row g-2">
          <div class="col-md-9">
            <input type="text" class="form-control" v-model="authorizationNote" placeholder="e.g. Authorized pending PSA submission" :disabled="isReadonly">
          </div>
          <div class="col-md-3">
            <button type="button" class="btn btn-submit w-100" :disabled="isReadonly" @click="setAuthorization">Save note</button>
          </div>
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
            <p>Check off only what the applicant has physically handed over today. PSA/NSO Birth Certificate and Certificate of Good Moral must be on hand — everything else can be marked "To follow" if the applicant doesn't have it yet.</p>
          </div>
        </div>

        <div class="doc-checklist">
          <div class="doc-item" v-for="doc in requiredDocs" :key="doc">
            <input type="checkbox" :id="doc" v-model="checkedDocs" :value="doc" :disabled="isReadonly || laterDocs.includes(doc)">
            <label :for="doc">{{ doc }}</label>
            <template v-if="onlineRecordFor(doc)">
              <a v-if="onlineRecordFor(doc).file_path"
                 :href="documentViewUrl(onlineRecordFor(doc))"
                 target="_blank"
                 rel="noopener"
                 class="doc-view-btn"
                 @click.prevent="openPreview(onlineRecordFor(doc), doc, $event)">
                <iconify-icon icon="mdi:file-eye-outline"></iconify-icon> View soft copy
              </a>
              <span v-else class="doc-online-badge doc-online-badge--later">
                <iconify-icon icon="mdi:clock-outline"></iconify-icon> Said they'd bring at campus
              </span>
            </template>
            <span v-else class="doc-online-badge doc-online-badge--none">Not submitted online</span>
            <span class="req" v-if="isCritical(doc)">Required</span>
            <label class="doc-later" v-else>
              <input type="checkbox" :checked="laterDocs.includes(doc)" :disabled="isReadonly" @change="toggleLater(doc)">
              To follow
            </label>
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
              <input type="checkbox" :id="'cr_'+sub.subject_id" v-model="creditedSubjectIds" :value="sub.subject_id" :disabled="isReadonly">
              <label :for="'cr_'+sub.subject_id">{{ sub.subject_code }} — {{ sub.subject_name }} ({{ sub.year_level }}Y, Sem {{ sub.semester }})</label>
            </div>
          </div>
        </div>

        <div v-if="confirmError" class="alert-box alert-error mt-3">{{ confirmError }}</div>

        <div class="form-actions">
          <span class="hint">This finalizes verification — it can't be undone from here.</span>
          <button type="button" class="btn btn-submit" @click="confirm" :disabled="isReadonly || confirming">
            {{ confirming ? 'Confirming…' : 'Confirm admission' }}
          </button>
        </div>
      </div>

      <Transition name="doc-preview-fade">
        <div v-if="previewDoc" class="doc-preview-overlay" @click.self="closePreview">
          <Transition name="doc-preview-pop">
            <div class="doc-preview-panel" :style="{ '--preview-origin': previewOrigin }">
              <div class="doc-preview-head">
                <span class="doc-preview-title">
                  <iconify-icon icon="mdi:file-eye-outline"></iconify-icon>
                  {{ previewDoc.label }}
                </span>
                <div class="doc-preview-actions">
                  <a class="doc-preview-btn" :href="documentViewUrl(previewDoc)" target="_blank" rel="noopener" title="Open in new tab">
                    <iconify-icon icon="mdi:open-in-new"></iconify-icon>
                  </a>
                  <button type="button" class="doc-preview-btn doc-preview-btn--close" @click="closePreview" title="Close">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                  </button>
                </div>
              </div>
              <div class="doc-preview-body">
                <img v-if="isImageDoc(previewDoc)" :src="documentViewUrl(previewDoc)" :alt="previewDoc.label">
                <iframe v-else :src="documentViewUrl(previewDoc)" :title="previewDoc.label"></iframe>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>

    </div>

  </div>
</div>

</main>

</div>

<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
$extraScripts = ['/SIAdrafts/Frontend/Js/Admission/admission-confirm.js'];
include '../Include/footer.php';
?>