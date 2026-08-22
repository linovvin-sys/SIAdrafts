<?php
// $extraScripts is the variable name several pages (treasury.php,
// total_enrolees.php, enrollment_confirm.php, etc.) actually set, but this
// loop previously only read $page_scripts — so every one of those pages'
// own JS silently never loaded. Support both names rather than renaming
// every call site.
$page_scripts = array_merge($page_scripts ?? [], $extraScripts ?? []);
?>
  <!--<script src="/SIAdrafts/Frontend/Js/Admission/nav-scroll.js"></script>-->
  <script src="/SIAdrafts/Frontend/Js/Admission/nav-toggle.js"></script>
  <script src="/SIAdrafts/Frontend/Js/Admission/nav-user-dropdown.js"></script>
  <script src="/SIAdrafts/Frontend/Js/Admission/main.js"></script>
  <script src="/SIAdrafts/Frontend/Js/Admission/login.js"></script>
   <script src="/SIAdrafts/Frontend/Js/Admission/confirm.js"></script>
  <script src="/SIAdrafts/Frontend/Js/required-marker.js"></script>

  
<?php foreach ($page_scripts as $_s): ?>
  <script src="<?= htmlspecialchars($_s, ENT_QUOTES) ?>"></script>
<?php endforeach; ?>
  <script src="/SIAdrafts/Frontend/Js/Admission/main.js"></script>
</body>
</html>