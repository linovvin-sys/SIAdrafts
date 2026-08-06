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
    ['label' => 'Pending Documents', 'page' => 'pending_documents', 'url' => '/SIAdrafts/Frontend/View/Admission/pending_documents.php', 'icon' => 'approval'],
    ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Admission/total_enrolees.php', 'icon' => 'enrolees'],
];

$staffRoleNav = [
    ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Admission/dashboard.php', 'icon' => 'dashboard'],
    ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/Admission/enrollment.php', 'icon' => 'enrollment'],
    ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Admission/total_enrolees.php', 'icon' => 'enrolees'],
];

$treasuryRoleNav = [
    ['label' => 'Treasury', 'page' => 'treasury', 'url' => '/SIAdrafts/Frontend/View/Admission/treasury.php', 'icon' => 'treasury'],
    [
        'group' => 'Revenue',
        'icon' => 'revenue',
        'items' => [
            ['label' => 'Paid', 'page' => 'revenue_paid', 'url' => '/SIAdrafts/Frontend/View/Admission/revenue_paid.php', 'icon' => 'paid'],
            ['label' => 'Process', 'page' => 'revenue_process', 'url' => '/SIAdrafts/Frontend/View/Admission/revenue_process.php', 'icon' => 'process'],
            ['label' => 'Unpaid', 'page' => 'unpaid_students', 'url' => '/SIAdrafts/Frontend/View/Admission/unpaid_students.php', 'icon' => 'unpaid'],
        ],
    ],
];

return [
    'admission' => $admissionRoleNav,
    'staff'     => $staffRoleNav,
    'treasury'  => $treasuryRoleNav,
    'professor' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Professor/professor_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'My Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/Professor/schedule.php', 'icon' => 'schedule'],
        ['label' => 'My Classes', 'page' => 'classes', 'url' => '/SIAdrafts/Frontend/View/Professor/classes.php', 'icon' => 'sections'],
        ['label' => 'My Profile', 'page' => 'profile', 'url' => '/SIAdrafts/Frontend/View/Professor/profile.php', 'icon' => 'settings'],
    ],
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
                ['label' => 'Courses', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/Registrar/courses.php', 'icon' => 'course'],
                ['label' => 'Sections', 'page' => 'sections', 'url' => '/SIAdrafts/Frontend/View/Registrar/sections.php', 'icon' => 'sections'],
                ['label' => 'Subjects', 'page' => 'subjects', 'url' => '/SIAdrafts/Frontend/View/Registrar/subjects.php', 'icon' => 'subjects'],
                ['label' => 'Professors', 'page' => 'professors', 'url' => '/SIAdrafts/Frontend/View/Registrar/professors.php', 'icon' => 'professors'],
                ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/Registrar/schedule.php', 'icon' => 'schedule'],
                ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/Registrar/add_drop_subject.php', 'icon' => 'addDrop'],
                ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/Registrar/readmission_request.php', 'icon' => 'readmission'],
            ],
        ],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/Registrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
        ['label' => 'Notifications', 'page' => 'notifications', 'url' => '/SIAdrafts/Frontend/View/Registrar/notifications.php', 'icon' => 'notifications'],
    ],
    'head registrar' => [
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
                ['label' => 'Courses', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/Registrar/courses.php', 'icon' => 'course'],
                ['label' => 'Sections', 'page' => 'sections', 'url' => '/SIAdrafts/Frontend/View/Registrar/sections.php', 'icon' => 'sections'],
                ['label' => 'Subjects', 'page' => 'subjects', 'url' => '/SIAdrafts/Frontend/View/Registrar/subjects.php', 'icon' => 'subjects'],
                ['label' => 'Professors', 'page' => 'professors', 'url' => '/SIAdrafts/Frontend/View/Registrar/professors.php', 'icon' => 'professors'],
                ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/Registrar/schedule.php', 'icon' => 'schedule'],
                ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/Registrar/add_drop_subject.php', 'icon' => 'addDrop'],
                ['label' => 'Pending Approval', 'page' => 'pending', 'url' => '/SIAdrafts/Frontend/View/Registrar/pending_approval.php', 'icon' => 'approval', 'badge' => 'pending'],
                ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/Registrar/readmission_request.php', 'icon' => 'readmission'],
            ],
        ],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/Registrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
        ['label' => 'Notifications', 'page' => 'notifications', 'url' => '/SIAdrafts/Frontend/View/Registrar/notifications.php', 'icon' => 'notifications'],
        ['label' => 'Settings', 'page' => 'settings', 'url' => '/SIAdrafts/Frontend/View/Admin/settings.php', 'icon' => 'settings'],
    ],
];
