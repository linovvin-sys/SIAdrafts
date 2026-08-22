<?php $extraScripts = $extraScripts ?? []; ?>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/Admin/confirm.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/Admin/admin.js'), ENT_QUOTES) ?>"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/datatable-init.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Js/required-marker.js'), ENT_QUOTES) ?>"></script>
<?php foreach ($extraScripts as $src): ?>
  <script src="<?= htmlspecialchars(asset_url($src), ENT_QUOTES) ?>"></script>
<?php endforeach; ?>
</body>
</html>
