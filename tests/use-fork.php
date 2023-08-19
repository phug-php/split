<?php

[, $package, $version, $url, $branch] = $argv;

$composer = (array) json_decode(file_get_contents('composer.json'), true);
$repositories = array_filter($composer['repositories'] ?? [], static function ($repository) use ($url) {
    return !is_array($repository) || !isset($repository['url']) || $repository['url'] !== $url;
});
$repositories[] = [
    'type' => 'vcs',
    'url' => $url,
];
$composer['repositories'] = $repositories;
$dev = isset($composer['require-dev'][$package]) ? ' --dev' : '';
file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo shell_exec(
    'composer require -n --no-update' . $dev . ' "' . $package . ':dev-' . $branch . ' as ' . $version . '"'
);
