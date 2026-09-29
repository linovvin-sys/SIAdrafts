<?php
/**
 * Generates a random temporary password for a newly-minted or reset
 * student portal account. Previously both callers derived a "temp"
 * password deterministically (lowercased last name + last 5 digits of
 * the student number) -- both pieces of information visible on
 * enrollment paperwork/IDs, so anyone who knew a student's name and
 * number could compute their login without ever going through a reset.
 * A random password closes that hole; it's still shown once to whoever
 * needs to hand it to the student (the enrollment email, or the Admin
 * reset screen), same as before.
 *
 * Excludes visually ambiguous characters (0/O, 1/l/I) since this is
 * sometimes read aloud to a student in person at the enrollment desk.
 */
function generate_temp_password(int $length = 10): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $max = strlen($alphabet) - 1;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $max)];
    }
    return $password;
}
