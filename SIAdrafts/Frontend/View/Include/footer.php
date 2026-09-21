<?php
require_once __DIR__ . '/../../../Backend/cdn_assets.php';
$extraScripts = $extraScripts ?? [];
?>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/Admin/confirm.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/Admin/admin.js'), ENT_QUOTES) ?>"></script>
<?= cdn_script_tag(CDN_JQUERY) ?>
<?= cdn_script_tag(CDN_DATATABLES_JS) ?>
<?= cdn_script_tag(CDN_DATATABLES_BS5_JS) ?>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/datatable-init.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/required-marker.js'), ENT_QUOTES) ?>"></script>
<?php if (!empty($_SESSION['user_id'])): ?>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/Admin/dotty-staff.js'), ENT_QUOTES) ?>"></script>
<?php endif; ?>
<?php foreach ($extraScripts as $src): ?>
  <?php if (isset(CDN_LEGACY_ALIASES[$src]) || isset(CDN_INTEGRITY[$src])): ?>
    <?= cdn_script_tag($src) ?>
  <?php else: ?>
    <script src="<?= htmlspecialchars(asset_url($src), ENT_QUOTES) ?>"></script>
  <?php endif; ?>
<?php endforeach; ?>
</body>
</html>
