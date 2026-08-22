</main>

<footer class="sp-footer">
  <p>Edu<em>School</em> Student Portal · Need help? Contact the Registrar's Office.</p>
</footer>

<nav class="sp-tabbar" aria-label="Student portal, mobile">
  <?php foreach ($_tabs as $t): ?>
    <a class="sp-tab <?= ($activePage ?? '') === $t['page'] ? 'active' : '' ?>" href="<?= $t['url'] ?>">
      <iconify-icon icon="<?= $t['icon'] ?>"></iconify-icon>
      <span><?= $t['label'] ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<div class="sp-toast-host" id="spToastHost" aria-live="polite"></div>

<dialog class="sp-dialog" id="spConfirmDialog">
  <div class="sp-dialog-body">
    <p class="sp-dialog-title" id="spConfirmTitle">Are you sure?</p>
    <p class="sp-dialog-message" id="spConfirmMessage"></p>
    <div class="sp-dialog-actions">
      <button type="button" class="sp-btn sp-btn-secondary" id="spConfirmCancel">Cancel</button>
      <button type="button" class="sp-btn sp-btn-primary" id="spConfirmOk">Confirm</button>
    </div>
  </div>
</dialog>

<script src="/SIAdrafts/Frontend/Js/Student/shared.js"></script>
<script src="/SIAdrafts/Frontend/Js/required-marker.js"></script>
<?php if (!empty($pageScript)): ?>
<script src="/SIAdrafts/Frontend/Js/Student/<?= htmlspecialchars($pageScript, ENT_QUOTES) ?>.js"></script>
<?php endif; ?>

</body>
</html>
