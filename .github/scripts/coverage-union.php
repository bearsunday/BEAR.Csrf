<?php

declare(strict_types=1);

/**
 * Merges Clover reports from every CI leg and fails unless the union covers every line.
 *
 * One leg cannot reach 100% on its own: SessionCsrfStore's coroutine guard has a branch for
 * each answer to "is Swoole loaded?", and a leg has only one answer. Across the matrix both
 * run. codecov would compute the same union, but the gate must not depend on an upload that
 * can fail silently — it did for a month after the repository transfer.
 *
 * Usage: php coverage-union.php clover-1.xml [clover-2.xml ...]
 */

if ($argc < 2) {
    fwrite(STDERR, "usage: coverage-union.php <clover.xml>...\n");
    exit(2);
}

/** @var array<string, array<int, int>> $hits file => [line => hits] */
$hits = [];
foreach (array_slice($argv, 1) as $path) {
    $xml = simplexml_load_file($path);
    if ($xml === false) {
        fwrite(STDERR, "cannot parse {$path}\n");
        exit(2);
    }

    foreach ($xml->xpath('//file') as $file) {
        $name = (string) $file['name'];
        foreach ($file->line as $line) {
            $num = (int) $line['num'];
            $hits[$name][$num] = ($hits[$name][$num] ?? 0) + (int) $line['count'];
        }
    }
}

$total = 0;
$missed = [];
foreach ($hits as $name => $lines) {
    foreach ($lines as $num => $count) {
        $total++;
        if ($count === 0) {
            $missed[] = sprintf('%s:%d', preg_replace('#^.*?/src/#', 'src/', $name), $num);
        }
    }
}

$covered = $total - count($missed);
printf("union coverage: %d/%d lines (%.2f%%) across %d report(s)\n", $covered, $total, $total > 0 ? 100 * $covered / $total : 0, $argc - 1);

if ($missed !== []) {
    echo "uncovered:\n";
    foreach ($missed as $m) {
        echo "  {$m}\n";
    }

    exit(1);
}
