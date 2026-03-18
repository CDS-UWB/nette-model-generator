<?php

namespace Cds\NetteModelGenerator;

use UnexpectedValueException;

class Utils
{
    public static function snakeToPascalCase(string $input): string
    {
        return str_replace('_', '', mb_convert_case($input, MB_CASE_TITLE, 'UTF-8'));
    }

    public static function snakeToCamelCase(string $input): string
    {
        $result = self::snakeToPascalCase($input);

        return strtolower($result[0]) . substr($result, 1);
    }

    public static function sanitizeVariableName(string $name, bool $isConstOrEnum): string
    {
        $sep = '_';

        $result = self::convertToAscii($name);

        $result = strtolower($result);

        // Replace all characters other than list with separator
        $result = self::replaceOrThrow('/[^a-z0-9]+/u', $sep, $result);

        // Collapse multiple separators into one
        $result = self::replaceOrThrow('/' . preg_quote($sep, '/') . '{2,}/', $sep, $result);

        $result = $isConstOrEnum ? self::snakeToPascalCase($result) : self::snakeToCamelCase($result);

        // If name starts with number add separator to the start
        if (preg_match('/^\d/', $result)) {
            $result = $sep . $result;
        }

        return $result;
    }

    public static function convertToAscii(string $text): string
    {
        $result = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($result === false) {
            throw new UnexpectedValueException("Unexpected value for variable name: {$text}");
        }

        return self::replaceOrThrow('/[^\x20-\x7E]/', '', $result);
    }

    private static function replaceOrThrow(string $pattern, string $replacement, string $subject): string
    {
        $result = preg_replace($pattern, $replacement, $subject);
        if ($result === null) {
            throw new UnexpectedValueException("Unexpected value for variable name: {$subject}");
        }

        return $result;
    }
}
