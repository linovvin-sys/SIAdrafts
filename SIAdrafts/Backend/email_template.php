<?php

/**
 * Shared HTML layout for every transactional email the system sends, so
 * they all carry the same white-and-green look as the portal itself
 * (brand green #046A38, ink #1F2E28, paper #F8F9F7 -- the same tokens the
 * Frontend/Css files use).
 *
 * Email clients ignore most modern CSS, so everything here is table-based
 * with inline styles, and the "logo" is a CSS monogram rather than an
 * <img>: the real crest is a relative-path SVG, which Gmail/Outlook won't
 * render and which wouldn't resolve outside the site anyway.
 *
 * Every caller-supplied string is escaped here; pass plain text.
 */

const EMAIL_GREEN      = '#046A38';
const EMAIL_GREEN_DARK = '#03512A';
const EMAIL_GREEN_TINT = '#E2EFE8';
const EMAIL_INK        = '#1F2E28';
const EMAIL_MUTED      = '#5C6B63';
const EMAIL_LINE       = '#DCE5DF';
const EMAIL_PAPER      = '#F8F9F7';

function email_e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Two-column label/value rows. $rows = [[label, value, ?monospace], ...] */
function email_details(array $rows): string
{
    $out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin:0 0 4px;">';
    $last = count($rows) - 1;
    foreach (array_values($rows) as $i => $row) {
        $border = $i < $last ? 'border-bottom:1px solid ' . EMAIL_LINE . ';' : '';
        $mono   = !empty($row[2]) ? 'font-family:Courier New,Courier,monospace;' : '';
        $out .= '<tr>'
            . '<td style="padding:12px 0;color:' . EMAIL_MUTED . ';' . $border . '">' . email_e($row[0]) . '</td>'
            . '<td style="padding:12px 0;text-align:right;font-weight:bold;color:' . EMAIL_INK . ';' . $mono . $border . '">' . email_e($row[1]) . '</td>'
            . '</tr>';
    }
    return $out . '</table>';
}

/** Big highlighted value (reference number, temporary password...). */
function email_highlight(string $label, string $value): string
{
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 22px;">'
        . '<tr><td align="center" style="background:' . EMAIL_GREEN_TINT . ';border:1px solid #B9D8C6;border-radius:12px;padding:20px 16px;">'
        . '<div style="font-size:11px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:' . EMAIL_GREEN . ';margin-bottom:8px;">' . email_e($label) . '</div>'
        . '<div style="font-family:Courier New,Courier,monospace;font-size:26px;font-weight:bold;letter-spacing:2px;color:' . EMAIL_GREEN_DARK . ';">' . email_e($value) . '</div>'
        . '</td></tr></table>';
}

/** Numbered "what happens next" list. */
function email_steps(string $heading, array $steps): string
{
    $out = '<div style="font-size:13px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:' . EMAIL_GREEN . ';margin:26px 0 12px;">' . email_e($heading) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">';
    foreach (array_values($steps) as $i => $text) {
        $out .= '<tr>'
            . '<td width="34" valign="top" style="padding:0 0 12px;">'
            . '<div style="width:24px;height:24px;line-height:24px;border-radius:12px;background:' . EMAIL_GREEN . ';color:#FFFFFF;font-size:12px;font-weight:bold;text-align:center;">' . ($i + 1) . '</div>'
            . '</td>'
            . '<td valign="top" style="padding:2px 0 12px;font-size:14px;line-height:1.55;color:' . EMAIL_INK . ';">' . email_e($text) . '</td>'
            . '</tr>';
    }
    return $out . '</table>';
}

/** Soft green note box, used for tips and security reminders. */
function email_callout(string $title, string $text): string
{
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 0;">'
        . '<tr><td style="background:' . EMAIL_PAPER . ';border-left:4px solid ' . EMAIL_GREEN . ';border-radius:6px;padding:14px 16px;">'
        . '<div style="font-size:13px;font-weight:bold;color:' . EMAIL_INK . ';margin-bottom:4px;">' . email_e($title) . '</div>'
        . '<div style="font-size:13px;line-height:1.6;color:' . EMAIL_MUTED . ';">' . email_e($text) . '</div>'
        . '</td></tr></table>';
}

function email_paragraph(string $text): string
{
    return '<p style="margin:0 0 18px;font-size:15px;line-height:1.65;color:' . EMAIL_INK . ';">' . email_e($text) . '</p>';
}

/**
 * Wraps body HTML in the branded shell.
 *
 * @param string $schoolName  School display name (plain text)
 * @param string $eyebrow     Small label above the title, e.g. "Admissions Office"
 * @param string $title       Headline shown in the green banner
 * @param string $bodyHtml    Pre-built content (use the helpers above)
 * @param string $sender      Office signing off, e.g. "Registrar's Office"
 * @param string $preheader   Inbox preview text
 */
function email_layout(string $schoolName, string $eyebrow, string $title, string $bodyHtml, string $sender, string $preheader = ''): string
{
    $initial = email_e(mb_strtoupper(mb_substr(trim($schoolName), 0, 1)) ?: 'S');
    $name    = email_e($schoolName);

    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="color-scheme" content="light"><title>' . email_e($title) . '</title></head>'
        . '<body style="margin:0;padding:0;background:' . EMAIL_PAPER . ';">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . email_e($preheader) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . EMAIL_PAPER . ';">'
        . '<tr><td align="center" style="padding:32px 12px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;font-family:Helvetica,Arial,sans-serif;color:' . EMAIL_INK . ';">'

        // Brand bar
        . '<tr><td style="background:' . EMAIL_GREEN . ';border-radius:16px 16px 0 0;padding:26px 32px 0;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td valign="middle"><div style="width:42px;height:42px;line-height:42px;border-radius:21px;background:#FFFFFF;color:' . EMAIL_GREEN . ';font-family:Georgia,serif;font-size:21px;font-weight:bold;text-align:center;">' . $initial . '</div></td>'
        . '<td valign="middle" style="padding-left:12px;font-family:Georgia,serif;font-size:19px;font-weight:bold;color:#FFFFFF;">' . $name . '</td>'
        . '</tr></table>'
        . '</td></tr>'

        // Headline
        . '<tr><td style="background:' . EMAIL_GREEN . ';padding:22px 32px 34px;">'
        . '<div style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#BFE0CD;margin-bottom:8px;">' . email_e($eyebrow) . '</div>'
        . '<div style="font-family:Georgia,serif;font-size:27px;line-height:1.25;font-weight:bold;color:#FFFFFF;">' . email_e($title) . '</div>'
        . '</td></tr>'

        // Card body
        . '<tr><td style="background:#FFFFFF;border:1px solid ' . EMAIL_LINE . ';border-top:0;padding:32px;">'
        . $bodyHtml
        . '<div style="margin-top:30px;padding-top:20px;border-top:1px solid ' . EMAIL_LINE . ';font-size:14px;line-height:1.6;color:' . EMAIL_MUTED . ';">'
        . 'Warm regards,<br><strong style="color:' . EMAIL_INK . ';">' . email_e($sender) . '</strong><br>' . $name
        . '</div>'
        . '</td></tr>'

        // Footer accent + legal line
        . '<tr><td style="background:' . EMAIL_GREEN_TINT . ';border:1px solid ' . EMAIL_LINE . ';border-top:0;border-radius:0 0 16px 16px;padding:16px 32px;text-align:center;font-size:11px;line-height:1.6;color:' . EMAIL_MUTED . ';">'
        . 'This is an automated message from ' . $name . ' &mdash; replies are not monitored.<br>'
        . 'Never share your password or reference number with anyone.'
        . '</td></tr>'

        . '</table></td></tr></table></body></html>';
}

/** Admission slip sent when an application is filed. */
function email_admission_slip(string $brandName, array $fields, string $applicantName, string $referenceId): string
{
    return email_layout(
        $brandName,
        'Admissions Office',
        'Your application is in!',
        email_paragraph('Hi ' . $fields['first_name'] . ', thank you for choosing ' . $brandName . '. We have received your application and it is now being processed.')
        . email_highlight('Your reference number', $referenceId)
        . email_details([
            ['Applicant', $applicantName],
            ['Program', $fields['program'] . ' — ' . $fields['year_level']],
            ['Term', $fields['school_year'] . ' — Semester ' . $fields['semester']],
            ['Date filed', date('F j, Y')],
        ])
        . email_steps('What happens next', [
            'Keep this email. Print the slip or show it on your phone when you visit campus.',
            'Bring your Certificate of Good Moral, PSA birth certificate, Form 138, and 2x2 photos for document verification.',
            'Once your documents are verified, the registrar will complete your enrollment and issue your student portal account.',
        ])
        . email_callout('Tip', 'Your reference number is how the admissions office finds your application, so keep it handy.'),
        'Admissions Office',
        'We received your application. Reference ' . $referenceId
    );
}

/** Welcome email with the new student's portal credentials. */
function email_portal_welcome(string $brandName, string $firstName, array $credentials): string
{
    return email_layout(
        $brandName,
        "Registrar's Office",
        'Welcome to ' . $brandName . '!',
        email_paragraph('Hi ' . $firstName . ', your enrollment is complete and your student portal account is ready.')
        . email_highlight('Temporary password', $credentials['temp_password'])
        . email_details([
            ['Student No.', $credentials['student_no']],
            ['Portal', 'Student Portal'],
        ])
        . email_steps('Getting started', [
            'Open the Student Portal and sign in with your student number and the temporary password above.',
            'You will be asked to set your own password right away.',
            'Explore your schedule, grades, announcements, and class materials.',
        ])
        . email_callout('Keep it secure', 'The temporary password stops working as soon as you set your own. Never share it with anyone.'),
        "Registrar's Office",
        'Your student portal account is ready. Student No. ' . $credentials['student_no']
    );
}

/** Password reset email carrying a new temporary password. */
function email_password_reset(string $brandName, string $firstName, string $studentNo, string $tempPassword): string
{
    return email_layout(
        $brandName,
        'Student Portal Support',
        'Your password was reset',
        email_paragraph('Hi ' . $firstName . ', the password for your ' . $brandName . ' student portal account was just reset.')
        . email_highlight('Temporary password', $tempPassword)
        . email_details([
            ['Student No.', $studentNo],
        ])
        . email_steps('To sign back in', [
            'Open the Student Portal and sign in with your student number and the temporary password above.',
            'Choose a new password when prompted. The temporary one stops working once you do.',
        ])
        . email_callout("Didn't request this?", "If you didn't ask for a reset, contact the school administration right away so your account can be secured."),
        'School Administration',
        'Your temporary password is inside.'
    );
}
