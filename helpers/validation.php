<?php
// helpers/validation.php

function validate_positive_number($value, $fieldName = 'Nilai') {
    if (!is_numeric($value) || (float)$value < 0) {
        return "$fieldName tidak boleh negatif dan harus berupa angka valid.";
    }
    return null;
}

function validate_positive_int($value, $fieldName = 'Jumlah') {
    $val = filter_var($value, FILTER_VALIDATE_INT);
    if ($val === false || $val <= 0) {
        return "$fieldName harus berupa bilangan bulat positif lebih besar dari nol.";
    }
    return null;
}

function validate_required($value, $fieldName = 'Kolom') {
    if (empty(trim((string)$value))) {
        return "$fieldName tidak boleh kosong.";
    }
    return null;
}
