<?php
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

class PSR4Refactor {
    private $rootDir;
    private $oldToNewNamespaces = [];
    private $oldToNewClasses = [];
    private $processedFiles = [];

    public function __construct() {
        $this->rootDir = __DIR__;
        
        // Определяем маппинг старых неймспейсов на новые
        $this->oldToNewNamespaces = [
            'WeppsCore' => 'Wepps\\Core',
            'WeppsExtensions' => 'Wepps\\Extensions',
            'WeppsAdmin' => 'Wepps\\Admin'
        ];

        // Определяем маппинг старых имен классов на новые
        $this->oldToNewClasses = [
            // Core classes
            'UtilsWepps' => 'Utils',
            'ConnectWepps' => 'Connection',
            'SmartyWepps' => 'Smarty',
            'JwtWepps' => 'Jwt',
            'RequestWepps' => 'Request',
            'TemplateHeadersWepps' => 'TemplateHeaders',
            'CliWepps' => 'Cli',
            'UsersWepps' => 'Users',
            'MemcachedWepps' => 'Cache',
            'LogsWepps' => 'Logger',
            'DataWepps' => 'Data',
            'NavigatorWepps' => 'Navigator',
            'NavigatorDataWepps' => 'NavigatorData',
            'ExtensionWepps' => 'Extension',
            'LanguageWepps' => 'Language',
            'PermissionsWepps' => 'Permissions',
            
            // Extensions

            'RequestExample11Wepps' => 'Example11Request',
            'TilesWepps' => 'Tiles',
            'TemplateWepps' => 'Template',
            'TemplateUtilsWepps' => 'TemplateUtils',
            'TemplateAddonsWepps' => 'TemplateAddons',
            'ServicesWepps' => 'Services',
            'RequestServicesWepps' => 'ServicesRequest',
            'RequestAddonsWepps' => 'AddonsRequest',
            'SuggestionsWepps' => 'Suggestions',
            'LayoutWepps' => 'Layout',
            'FormWepps' => 'Form',
            'FiltersWepps' => 'Filters',
            'BlocksWepps' => 'Blocks',
            'RequestBlocksWepps' => 'BlocksRequest',
            'ExampleWepps' => 'Example',
            'AccordionPanelWepps' => 'AccordionPanel',
            'ProfileWepps' => 'Profile',

            // Admin Classes
            'NavigatorAdWepps' => 'NavigatorAd',
            'RequestNavigatorAdWepps' => 'NavigatorAdRequest',
            'UpdatesMethodsWepps' => 'UpdatesMethods',
            'UpdatesWepps' => 'Updates',
            'HomeWepps' => 'Home',
            'RequestListsWepps' => 'ListsRequest',
            'SaveItemWepps' => 'SaveItem',
            'RemoveItemDirectoriesWepps' => 'RemoveItemDirectories',
            'SaveItemConfigExtensionsWepps' => 'SaveItemConfigExtensions',
            'SaveItemConfigFieldsWepps' => 'SaveItemConfigFields',
            'SaveItemProductsWepps' => 'SaveItemProducts',
            'SaveItemExtensionsWepps' => 'SaveItemExtensions',
            'ViewItemWepps' => 'ViewItem',
            'SaveItemTemplatesWepps' => 'SaveItemTemplates',
            'ViewListWepps' => 'ViewList',
            'ViewItemDirectoriesWepps' => 'ViewItemDirectories',
            'SaveItemProductsVariationsWepps' => 'SaveItemProductsVariations',
            'SaveItemDirectoriesWepps' => 'SaveItemDirectories',
            'SaveItemConfigWepps' => 'SaveItemConfig',
            'RemoveItemConfigFieldsWepps' => 'RemoveItemConfigFields'
        ];
    }

    public function run() {
        echo "Starting PSR-4 refactoring...\n";
        
        // Обновляем composer.json
        if (!$this->updateComposerJson()) {
            echo "Failed to update composer.json, stopping refactoring\n";
            return;
        }
        
        // Обрабатываем файлы
        $this->processDirectory($this->rootDir);
        
        echo "\nRefactoring completed!\n";
        echo "Processed files: " . count($this->processedFiles) . "\n";
        echo "\nPlease run 'composer dump-autoload' to update the autoloader\n";
    }

    private function processDirectory($dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->processFile($file->getRealPath());
        }
    }

    private function processFile($filePath) {
        echo "Processing: " . basename($filePath) . "\n";
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            echo "Error reading file: $filePath\n";
            return;
        }

        // Сначала проверяем, содержит ли файл несколько классов
        $matches = [];
        preg_match_all('/class\s+(\w+)(?:\s+extends\s+\w+)?\s*{/', $content, $matches);
        
        if (count($matches[1]) > 1) {
            echo "Found multiple classes in " . basename($filePath) . ". Splitting...\n";
            $this->splitClassesIntoFiles($filePath, $content);
            return;
        }

        $newContent = $content;

        // Заменяем namespace
        foreach ($this->oldToNewNamespaces as $old => $new) {
            $newContent = preg_replace(
                "/namespace\s+$old(\\\\\w+)?;/",
                "namespace $new$1;",
                $newContent
            );
            
            // Заменяем use statements
            $newContent = preg_replace(
                "/use\s+$old\\\\/",
                "use $new\\\\",
                $newContent
            );
        }

        // Заменяем имена классов и их использование
        foreach ($this->oldToNewClasses as $old => $new) {
            // Замена объявления класса
            $newContent = preg_replace(
                "/class\s+$old/",
                "class $new",
                $newContent
            );

            // Замена использования класса
            $newContent = preg_replace(
                "/new\s+$old\(/",
                "new $new(",
                $newContent
            );
            
            // Замена статических вызовов
            $newContent = preg_replace(
                "/$old::/",
                "$new::",
                $newContent
            );
            
            // Замена типов в параметрах и return type hints
            $newContent = preg_replace(
                "/([\s\(])$old([\s\)])/",
                "$1$new$2",
                $newContent
            );
        }

        if ($newContent !== $content) {
            if (file_put_contents($filePath, $newContent)) {
                $this->processedFiles[] = $filePath;
                echo "Updated: " . basename($filePath) . "\n";
            } else {
                echo "Error writing to file: $filePath\n";
            }
        }
    }

    private function createBackup($filePath) {
        $backupPath = $filePath . '.bak';
        return copy($filePath, $backupPath);
    }

    private function updateComposerJson() {
        $composerJsonPath = $this->rootDir . '/packages/composer.json';
        
        if (!file_exists($composerJsonPath)) {
            echo "Error: composer.json not found at $composerJsonPath\n";
            return false;
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error: Failed to parse composer.json\n";
            return false;
        }

        // Создаем backup composer.json
        $this->createBackup($composerJsonPath);

        // Добавляем секцию autoload если её нет
        if (!isset($composerJson['autoload'])) {
            $composerJson['autoload'] = [];
        }

        // Настраиваем PSR-4 автозагрузку
        $composerJson['autoload']['psr-4'] = [
            'Wepps\\Core\\' => 'WeppsCore/',
            'Wepps\\Extensions\\' => 'WeppsExtensions/',
            'Wepps\\Admin\\' => 'WeppsAdmin/'
        ];

        // Записываем обновленный composer.json
        if (file_put_contents(
            $composerJsonPath, 
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        )) {
            echo "Updated composer.json with PSR-4 autoload configuration\n";
            return true;
        }

        echo "Error: Failed to write updated composer.json\n";
        return false;
    }

    private function splitClassesIntoFiles($originalFilePath, $content) {
        // Получаем текущий namespace из файла
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Получаем все use statements
        $useStatements = [];
        preg_match_all('/use\s+[^;]+;/', $content, $matches);
        $useStatements = $matches[0];

        // Разбиваем файл на части по классам
        preg_match_all('/\/\*\*(?:(?!\*\/).)*\*\/\s*class\s+(\w+)(?:\s+extends\s+\w+)?\s*{(?:[^{}]+|{(?:[^{}]+|{[^{}]*})*})*}/s', $content, $matches, PREG_SET_ORDER);

        $dir = dirname($originalFilePath);
        
        foreach ($matches as $match) {
            $fullClass = $match[0]; // Весь класс с комментариями
            $className = $match[1]; // Имя класса
            
            // Создаем новый файл для класса
            $newClassName = str_replace(array_keys($this->oldToNewClasses), array_values($this->oldToNewClasses), $className);
            $newFilePath = $dir . DIRECTORY_SEPARATOR . $newClassName . '.php';
            
            // Формируем содержимое нового файла
            $newContent = "<?php\n\n";
            if ($namespace) {
                $newContent .= "namespace " . $namespace . ";\n\n";
            }
            if (!empty($useStatements)) {
                $newContent .= implode("\n", $useStatements) . "\n\n";
            }
            $newContent .= $fullClass;
            
            // Сохраняем новый файл
            if (file_put_contents($newFilePath, $newContent)) {
                echo "Created new file for class $className: " . basename($newFilePath) . "\n";
                $this->processedFiles[] = $newFilePath;
            }
        }

        // Создаем backup оригинального файла
        $this->createBackup($originalFilePath);
        
        // Удаляем оригинальный файл после успешного разделения
        unlink($originalFilePath);
        echo "Removed original file: " . basename($originalFilePath) . "\n";
    }
}

// Запускаем рефакторинг
echo "PSR-4 Refactoring Tool\n";
echo "=====================\n\n";

$refactor = new PSR4Refactor();
$refactor->run();
