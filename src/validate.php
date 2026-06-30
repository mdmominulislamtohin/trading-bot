<?php
// src/validate.php
// Small server-side validation helpers

function v_required($value): bool {
    if (is_array($value)) return !empty($value);
    return trim((string)$value) !== '';
}

function v_email($value): bool {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function v_min_length($value, int $min): bool {
    return mb_strlen((string)$value) >= $min;
}

function v_max_length($value, int $max): bool {
    return mb_strlen((string)$value) <= $max;
}
