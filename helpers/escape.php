<?php
// helpers/escape.php

function e($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function format_rupiah($number) {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}
