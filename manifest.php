<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$basePath = rtrim(path_to(''), '/') . '/';

header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'name' => 'Work Planner',
    'short_name' => 'Planner',
    'description' => 'Mobile-friendly weekly planning board for short absence and deviation codes.',
    'start_url' => path_to('board.php'),
    'scope' => $basePath,
    'display' => 'standalone',
    'background_color' => '#E9F1FA',
    'theme_color' => '#00ABE4',
    'orientation' => 'any',
    'icons' => [
        [
            'src' => path_to('assets/app-icon.svg'),
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
