<?php
$pageTitle = "COURSE AND SECTIONS";
$activePage = "courses";

include 'Include/header.php';
?>


  <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">
      <div class="grid-2">
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Courses</span>
            <button class="btn btn-primary">+ Add Course</button>
          </div>
          <div class="panel-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr><th>Code</th><th>Course Name</th><th>Units</th></tr>
              </thead>
              <tbody>
                <tr><td>BSCS</td><td>BS Computer Science</td><td>160</td></tr>
                <tr><td>BSACC</td><td>BS Accountancy</td><td>170</td></tr>
                <tr><td>BSN</td><td>BS Nursing</td><td>180</td></tr>
                <tr><td>BSED</td><td>BS Education</td><td>150</td></tr>
                <tr><td>BSENG</td><td>BS Engineering</td><td>175</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Sections</span>
            <button class="btn btn-primary">+ Add Section</button>
          </div>
          <div class="panel-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr><th>Section</th><th>Course</th><th>Capacity</th><th>Enrolled</th></tr>
              </thead>
              <tbody>
                <tr><td>CS-1A</td><td>BSCS</td><td>40</td><td>38</td></tr>
                <tr><td>CS-1B</td><td>BSCS</td><td>40</td><td>35</td></tr>
                <tr><td>NUR-1A</td><td>BSN</td><td>35</td><td>34</td></tr>
                <tr><td>ACC-2A</td><td>BSACC</td><td>40</td><td>28</td></tr>
                <tr><td>ENG-3A</td><td>BSENG</td><td>45</td><td>42</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php include 'Include/footer.php'?>
