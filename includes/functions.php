<?php

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarPreco($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>