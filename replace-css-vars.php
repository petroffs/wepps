#!/usr/bin/env php
<?php
// Запрос директории у пользователя
$directory = readline("Введите путь к папке для поиска CSS-файлов: ");
$directory = rtrim($directory, '/');

if (!is_dir($directory)) {
    die("Ошибка: указанная директория не существует.\n");
}

// Рекурсивный поиск CSS-файлов
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$cssFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'css') {
        $cssFiles[] = $file->getPathname();
    }
}

if (empty($cssFiles)) {
    die("CSS-файлы не найдены.\n");
}

echo "Найдено CSS-файлов: " . count($cssFiles) . "\n";
$confirm = readline("Заменить --step на --s и --stepHalf на --s-half? (y/n): ");

if (strtolower($confirm) !== 'y') {
    die("Отменено.\n");
}

$replacements = [
    '--stepHalf' => '--s-half',
    '--step' => '--s',
];

$totalFiles = 0;
$totalReplacements = 0;

foreach ($cssFiles as $filePath) {
    $content = file_get_contents($filePath);
    $modified = false;
    $fileReplacements = 0;

    foreach ($replacements as $search => $replace) {
        if (str_contains($content, $search)) {
            $content = str_replace($search, $replace, $content);
            $fileReplacements += substr_count($content, $replace);
            $modified = true;
        }
    }

    if ($modified) {
        file_put_contents($filePath, $content);
        $totalFiles++;
        $totalReplacements += $fileReplacements;
        echo "Обработан: $filePath\n";
    }
}

echo "\nГотово! Обработано файлов: $totalFiles, замен: $totalReplacements\n";