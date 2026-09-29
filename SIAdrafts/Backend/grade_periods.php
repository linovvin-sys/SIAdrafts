<?php
/**
 * Canonical grading-period strings, matching grade.period's enum values.
 * Shared by the professor-side write path (validation) and the
 * student-side read path (display order) so both stay in sync.
 */

const GRADE_PERIODS = ['Prelim', 'Midterm', 'Prefinal', 'Final'];

function is_valid_grade_period(string $period): bool
{
    return in_array($period, GRADE_PERIODS, true);
}
