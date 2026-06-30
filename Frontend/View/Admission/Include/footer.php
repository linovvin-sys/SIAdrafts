<?php $page_scripts = $page_scripts ?? []; ?>
  <script src="/SIAdrafts/Frontend/Js/Admission/nav-scroll.js"></script>
  <script src="/SIAdrafts/Frontend/Js/Admission/nav-toggle.js"></script>
  <script src="/SIAdrafts/Frontend/Js/Admission/nav-user-dropdown.js"></script>
<?php foreach ($page_scripts as $_s): ?>
  <script src="<?= htmlspecialchars($_s, ENT_QUOTES) ?>"></script>
<?php endforeach; ?>
  <!--<script src="/SIAdrafts/Frontend/Js/Admission/main.js"></script>-->
</body>
</html>