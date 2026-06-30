<?php
$pageTitle = "ADMISSION";
$activePage = "admission";
include 'Include/header.php';
?>


  <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">All Admission Records</span>
          <button class="btn btn-primary">+ New Application</button>
        </div>
        <div class="panel-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Applicant Name</th>
                <th>Course Applied</th>
                <th>Section</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>001</td>
                <td>Maria Santos</td>
                <td>BS Computer Science</td>
                <td>Jun 10, 2025</td>
                <td><span class="badge badge-success">Approved</span></td>
                <td><button class="btn btn-outline" style="padding:4px 10px;font-size:12px">View</button></td>
              </tr>
              <tr>
                <td>002</td>
                <td>Juan dela Cruz</td>
                <td>BS Accountancy</td>
                <td>Jun 11, 2025</td>
                <td><span class="badge badge-pending">Pending</span></td>
                <td><button class="btn btn-outline" style="padding:4px 10px;font-size:12px">View</button></td>
              </tr>
              <tr>
                <td>003</td>
                <td>Ana Reyes</td>
                <td>BS Nursing</td>
                <td>Jun 12, 2025</td>
                <td><span class="badge badge-success">Approved</span></td>
                <td><button class="btn btn-outline" style="padding:4px 10px;font-size:12px">View</button></td>
              </tr>
              <tr>
                <td>004</td>
                <td>Carlo Mendoza</td>
                <td>BS Education</td>
                <td>Jun 13, 2025</td>
                <td><span class="badge badge-danger">Rejected</span></td>
                <td><button class="btn btn-outline" style="padding:4px 10px;font-size:12px">View</button></td>
              </tr>
              <tr>
                <td>005</td>
                <td>Lea Villanueva</td>
                <td>BS Engineering</td>
                <td>Jun 14, 2025</td>
                <td><span class="badge badge-pending">Pending</span></td>
                <td><button class="btn btn-outline" style="padding:4px 10px;font-size:12px">View</button></td>
              </tr>
              
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<?php include 'Include/footer.php';?>
