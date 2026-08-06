<?php
/**
 * Canonical role name strings, matching roles.role_name values.
 * require_role() lowercases both sides, so exact casing here only
 * matters for readability at call sites.
 */

const ROLE_ADMIN          = 'Admin';
const ROLE_STAFF           = 'Staff';
const ROLE_TREASURY        = 'Treasury';
const ROLE_ADMISSION       = 'Admission';
const ROLE_REGISTRAR_STAFF = 'Registrar Staff';
const ROLE_HEAD_REGISTRAR  = 'Head Registrar';
const ROLE_PROFESSOR       = 'Professor';

const ROLES_ALL_STAFF = [
    ROLE_ADMIN,
    ROLE_STAFF,
    ROLE_TREASURY,
    ROLE_ADMISSION,
    ROLE_REGISTRAR_STAFF,
    ROLE_HEAD_REGISTRAR,
];
