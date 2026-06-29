
  <?php $page_scripts = $page_scripts ?? []; ?>
  <script src="js/nav-scroll.js"></script>
  <script src="js/nav-toggle.js"></script>
  <script src="js/nav-user-dropdown.js"></script>
<?php foreach ($page_scripts as $_s): ?>
  <script src="<?= htmlspecialchars($_s, ENT_QUOTES) ?>"></script>
<?php endforeach; ?>
  <script src="js/main.js"></script>
  <script src="js/login.js"></script>
</body>
</html>
