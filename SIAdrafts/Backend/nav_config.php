<?php
/**
 * Sidebar nav items per role. Keys are lowercase role_name values.
 * 'page' matches each view file's existing $activePage convention.
 *
 * Each entry is either:
 *   - a plain link:  ['label' => ..., 'page' => ..., 'url' => ..., 'icon' => ..., 'badge'? => ...]
 *   - a group:       ['group' => 'Label', 'icon' => ..., 'items' => [ <plain link>, ... ]]
 * Groups render as a collapsible dropdown in the sidebar; single links render flat.
 */

// 'admission' and 'staff' are two distinct DB roles that share the same
// Dashboard and Total Enrolees pages (both read-only/reporting), but the
// Admission role handles application intake/document verification while
// Staff handles enrollment processing — those two are role-exclusive, not
// shared, matching each page's require_role() gate.
$admissionRoleNav = [
    ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Admission/dashboard.php', 'icon' => 'dashboard'],
    ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/Admission/admission.php', 'icon' => 'admission'],
    ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Admission/total_enrolees.php', 'icon' => 'enrolees'],
];

$staffRoleNav = [
    ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Admission/dashboard.php', 'icon' => 'dashboard'],
    ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/Admission/enrollment.php', 'icon' => 'enrollment'],
    ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Admission/total_enrolees.php', 'icon' => 'enrolees'],
];

return [
    'admission' => $admissionRoleNav,
    'staff'     => $staffRoleNav,
    'admin' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Admin/admin_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Manage User', 'page' => 'manage_user', 'url' => '/SIAdrafts/Frontend/View/Admin/manage_user.php', 'icon' => 'users'],
        ['label' => 'Settings', 'page' => 'settings', 'url' => '/SIAdrafts/Frontend/View/Admin/settings.php', 'icon' => 'settings'],
    ],
    'registrar staff' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Registrar/registrar_dashboard.php', 'icon' => 'dashboard'],
        [
            'group' => 'Admissions',
            'icon' => 'admission',
            'items' => [
                ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/Registrar/admission.php', 'icon' => 'admission'],
                ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/Registrar/enrollment.php', 'icon' => 'enrollment'],
                ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Registrar/total_enrolees.php', 'icon' => 'enrolees'],
            ],
        ],
        [
            'group' => 'Management',
            'icon' => 'course',
            'items' => [
                ['label' => 'Course & Section', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/Registrar/courses.php', 'icon' => 'course'],
                ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/Registrar/schedule.php', 'icon' => 'schedule'],
                ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/Registrar/add_drop_subject.php', 'icon' => 'addDrop'],
                ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/Registrar/readmission_request.php', 'icon' => 'readmission'],
            ],
        ],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/Registrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
        ['label' => 'Notifications', 'page' => 'notifications', 'url' => '/SIAdrafts/Frontend/View/Registrar/notifications.php', 'icon' => 'notifications'],
    ],
    'head registrar' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/registrar_dashboard.php', 'icon' => 'dashboard'],
        [
            'group' => 'Admissions',
            'icon' => 'admission',
            'items' => [
                ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/admission.php', 'icon' => 'admission'],
                ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/enrollment.php', 'icon' => 'enrollment'],
                ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/total_enrolees.php', 'icon' => 'enrolees'],
            ],
        ],
        [
            'group' => 'Management',
            'icon' => 'course',
            'items' => [
                ['label' => 'Course & Section', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/courses.php', 'icon' => 'course'],
                ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/schedule.php', 'icon' => 'schedule'],
                ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/add_drop_subject.php', 'icon' => 'addDrop'],
                ['label' => 'Pending Approval', 'page' => 'pending', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/pending_approval.php', 'icon' => 'approval', 'badge' => 'pending'],
                ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/readmission_request.php', 'icon' => 'readmission'],
            ],
        ],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
        ['label' => 'Notifications', 'page' => 'notifications', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/notifications.php', 'icon' => 'notifications'],
        ['label' => 'Settings', 'page' => 'settings', 'url' => '/SIAdrafts/Frontend/View/Admin/settings.php', 'icon' => 'settings'],
    ],
];
