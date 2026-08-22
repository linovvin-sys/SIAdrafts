<?php $extraScripts = $extraScripts ?? []; ?>
<script src="/SIAdrafts/Frontend/Js/Admin/confirm.js"></script>
<script src="/SIAdrafts/Frontend/Js/Admin/admin.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="/SIAdrafts/Frontend/Js/datatable-init.js"></script>
<script src="/SIAdrafts/Frontend/Js/required-marker.js"></script>
<?php foreach ($extraScripts as $src): ?>
  <script src="<?= htmlspecialchars($src, ENT_QUOTES) ?>"></script>
<?php endforeach; ?>
</body>
</html>
