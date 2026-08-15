<?php
/**
 * Output encoding: the single point used by all views to render dynamic
 * values. Contextual output encoding (HTML context) neutralises stored and
 * reflected XSS. PHP's htmlspecialchars with ENT_QUOTES covers both single
 * and double quotes in attribute and text contexts.
 */
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
