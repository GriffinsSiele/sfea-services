<?php

declare(strict_types=1);

main();

function main(): void
{
    foreach (rglob(__DIR__ . '/../pkg/models') as $filename) {
        $go = file_get_contents($filename);

        preg_match_all(
            '~^\s*(?P<field>\w+).+?`(?P<tag>[^`]+)`~m',
            $go,
            $lines,
        );

        echo '--- ', $filename, PHP_EOL;
        echo '+++ ', $filename, PHP_EOL;

        foreach ($lines['tag'] as $i => $tag) {
            preg_match_all('~(\w+):"([^"]+)"~', $tag, $matches);

            $newTag = [];

            foreach ($matches[1] as $j => $modifier) {
                $options = explode(',', $matches[2][$j]);

                if (!in_array('omitempty', $options, true)) {
                    $options[] = 'omitempty';
                }

                $newTag[$modifier] = implode(',', $options);
            }

            if (!isset($newTag['json'])) {
                $newTag['json'] = implode(',', [
                    strtolower($lines['field'][$i]),
                    'omitempty',
                ]);
            }

            $newTagLineParts = [];

            foreach ($newTag as $key => $value) {
                $newTagLineParts[] = $key . ':"' . $value . '"';
            }

            $newTagLine = implode(' ', $newTagLineParts);

            if ($tag === $newTagLine) {
                continue;
            }

            echo '-', $lines[0][$i], PHP_EOL;

            $newLine = str_replace($tag, $newTagLine, $lines[0][$i]);

            echo '+', $newLine, PHP_EOL;

            $go = str_replace($lines[0][$i], $newLine, $go);
        }

        file_put_contents($filename, $go);
    }
}

function rglob(string $dir): iterable
{
    $iterator = new RegexIterator(
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        ),
        '~.*\.go$~',
        RegexIterator::GET_MATCH,
    );

    foreach ($iterator as $filenames) {
        foreach ($filenames as $filename) {
            yield realpath($filename);
        }
    }
}
