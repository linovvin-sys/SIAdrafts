<?php
/**
 * Sidebar nav items per role. Keys are lowercase role_name values.
 * 'page' matches each view file's existing $activePage convention.
 */
return [
    'admin' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Admin/admin_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Manage User', 'page' => 'manage_user', 'url' => '/SIAdrafts/Frontend/View/Admin/manage_user.php', 'icon' => 'users'],
        ['label' => 'Settings', 'page' => 'settings', 'url' => '/SIAdrafts/Frontend/View/Admin/settings.php', 'icon' => 'settings'],
    ],
    'registrar staff' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Registrar/registrar_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/Registrar/admission.php', 'icon' => 'admission'],
        ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/Registrar/enrollment.php', 'icon' => 'enrollment'],
        ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Registrar/total_enrolees.php', 'icon' => 'enrolees'],
        ['label' => 'Course & Section', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/Registrar/courses.php', 'icon' => 'course'],
        ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/Registrar/schedule.php', 'icon' => 'schedule'],
        ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/Registrar/add_drop_subject.php', 'icon' => 'addDrop'],
        ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/Registrar/readmission_request.php', 'icon' => 'readmission'],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/Registrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
    ],
    'head registrar' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/registrar_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/admission.php', 'icon' => 'admission'],
        ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/enrollment.php', 'icon' => 'enrollment'],
        ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/total_enrolees.php', 'icon' => 'enrolees'],
        ['label' => 'Course & Section', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/courses.php', 'icon' => 'course'],
        ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/schedule.php', 'icon' => 'schedule'],
        ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/add_drop_subject.php', 'icon' => 'addDrop'],
        ['label' => 'Pending Approval', 'page' => 'pending', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/pending_approval.php', 'icon' => 'approval', 'badge' => 'pending'],
        ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/readmission_request.php', 'icon' => 'readmission'],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
        ['label' => 'Settings', 'page' => 'settings', 'url' => '/SIAdrafts/Frontend/View/Admin/settings.php', 'icon' => 'settings'],
    ],
];
