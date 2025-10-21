<?php

namespace Webium\AssetsSync\Task;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;

/**
 * Import Files from Directory Task
 *
 * Imports all files from a specified directory in public/assets/ and publishes them
 * as File objects in the database.
 *
 * Usage:
 * ./vendor/bin/sake dev/tasks/ImportFilesFromDirectoryTask flush=1
 *
 * In DDEV:
 * ddev sake dev/tasks/ImportFilesFromDirectoryTask flush=1
 *
 * @author Bart van Irsel <bart@webium.nl>
 * @package Webium\AssetsSync
 */
class ImportFilesFromDirectoryTask extends BuildTask
{
    /**
     * @var string
     */
    protected $title = 'Import Files from Directory';

    /**
     * @var string
     */
    protected $description = 'Imports all files from a specified directory in public/assets/ and publishes them as File objects in the database';

    /**
     * The directory name within public/assets/ to import from
     * Can be configured via YML config:
     *
     * Webium\AssetsSync\Task\ImportFilesFromDirectoryTask:
     *   import_directory: 'my-import-folder'
     *
     * @var string
     * @config
     */
    private static $import_directory = 'import';

    /**
     * Run the import task
     *
     * @param \SilverStripe\Control\HTTPRequest $request
     * @return void
     */
    public function run($request)
    {
        $importDir = Config::inst()->get(self::class, 'import_directory');
        $basePath = Director::publicFolder() . '/assets/' . $importDir;

        echo "=== Import Files from Directory Task ===\n";
        echo "Importing from: {$basePath}\n";
        echo "Target folder in Assets: {$importDir}\n\n";

        // Check if directory exists
        if (!is_dir($basePath)) {
            echo "ERROR: Directory does not exist: {$basePath}\n";
            echo "Please create the directory and add files to import.\n";
            return;
        }

        // Find or create the target folder in Assets
        $folder = Folder::find_or_make($importDir);
        if ($folder) {
            echo "✓ Target folder ready (ID: {$folder->ID})\n\n";
        }

        // Get all files in the directory
        $files = $this->getFilesRecursively($basePath);

        if (empty($files)) {
            echo "No files found in directory: {$basePath}\n";
            return;
        }

        echo "Found " . count($files) . " file(s) to import\n\n";

        $imported = 0;
        $errors = 0;

        foreach ($files as $filePath) {
            try {
                $fileName = basename($filePath);

                // Calculate relative path from basePath
                $fileDir = dirname($filePath);
                $relativePath = '';
                if ($fileDir !== $basePath) {
                    $relativePath = str_replace($basePath . '/', '', $fileDir);
                }

                // Determine target folder path in Assets
                $targetFolderPath = $importDir;
                if ($relativePath && $relativePath !== $basePath) {
                    $targetFolderPath .= '/' . $relativePath;
                }

                // Check if file already exists in the target folder
                $existingFile = File::get()->filter([
                    'Name' => $fileName,
                    'ParentID' => Folder::find_or_make($targetFolderPath)->ID
                ])->first();

                if ($existingFile) {
                    echo "⚠ SKIPPED: {$fileName} (already exists in {$targetFolderPath} with ID: {$existingFile->ID})\n";
                    continue;
                }

                // Find or create the target folder
                $targetFolder = Folder::find_or_make($targetFolderPath);

                // Create new File object
                $file = new File();
                $file->setFromLocalFile($filePath, $fileName);
                $file->ParentID = $targetFolder->ID;
                $file->write();

                // Publish the file
                $file->publishSingle();

                echo "✓ IMPORTED: {$targetFolderPath}/{$fileName} (ID: {$file->ID})\n";
                $imported++;

            } catch (\Exception $e) {
                echo "✗ ERROR importing {$fileName}: " . $e->getMessage() . "\n";
                $errors++;
            }
        }

        echo "\n=== Import Complete ===\n";
        echo "Successfully imported: {$imported}\n";
        echo "Errors: {$errors}\n";
        echo "Skipped (already exists): " . (count($files) - $imported - $errors) . "\n";
    }

    /**
     * Recursively get all files from a directory
     *
     * @param string $dir The directory to scan
     * @return array Array of file paths
     */
    private function getFilesRecursively($dir)
    {
        $files = [];

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                // Recursively get files from subdirectory
                $files = array_merge($files, $this->getFilesRecursively($path));
            } else {
                // Add file to list
                $files[] = $path;
            }
        }

        return $files;
    }
}
