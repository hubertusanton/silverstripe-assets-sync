# SilverStripe Assets Sync

A SilverStripe module for importing files from a directory into the Assets system. This module provides a BuildTask that recursively scans a specified directory and imports all files into the SilverStripe database, maintaining the folder structure.

## Features

- **Recursive Import**: Automatically imports all files from subdirectories
- **Folder Structure Preservation**: Maintains the original directory structure in SilverStripe Assets
- **Duplicate Prevention**: Skips files that already exist in the target folder
- **Auto-Publishing**: Automatically publishes imported files
- **Configurable Source Directory**: Easily configure which directory to import from
- **Progress Reporting**: Clear feedback on import status, successes, and errors

## Installation

```bash
composer require webium/silverstripe-assets-sync
```

After installation, run:
```bash
vendor/bin/sake dev/build flush=1
```

Or with DDEV:
```bash
ddev exec vendor/bin/sake dev/build flush=1
```

## Usage

### 1. Create the Import Directory

By default, the module looks for files in `public/assets/import/`. Create this directory:

```bash
mkdir -p public/assets/import
```

### 2. Add Files to Import

Place the files you want to import in the `public/assets/import/` directory. You can organize them in subdirectories:

```
public/assets/import/
├── document1.pdf
├── image1.jpg
├── subfolder1/
│   ├── document2.pdf
│   └── image2.jpg
└── subfolder2/
    └── document3.pdf
```

### 3. Run the Import Task

Execute the import task via command line:

```bash
vendor/bin/sake dev/tasks/ImportFilesFromDirectoryTask flush=1
```

**With Docker:**
```bash
docker exec -it [container] vendor/bin/sake dev/tasks/ImportFilesFromDirectoryTask flush=1
```

**With DDEV:**
```bash
ddev exec vendor/bin/sake dev/tasks/ImportFilesFromDirectoryTask flush=1
```

### 4. View Results

The task will output:
- ✓ Successfully imported files with their database IDs
- ⚠ Skipped files that already exist
- ✗ Errors encountered during import
- Summary statistics at the end

Example output:
```
=== Import Files from Directory Task ===
Importing from: /path/to/public/assets/import
Target folder in Assets: import

✓ Target folder ready (ID: 123)

Found 5 file(s) to import

✓ IMPORTED: import/document1.pdf (ID: 124)
✓ IMPORTED: import/image1.jpg (ID: 125)
✓ IMPORTED: import/subfolder1/document2.pdf (ID: 126)
⚠ SKIPPED: image1.jpg (already exists in import with ID: 125)
✓ IMPORTED: import/subfolder2/document3.pdf (ID: 127)

=== Import Complete ===
Successfully imported: 4
Errors: 0
Skipped (already exists): 1
```

## Configuration

You can configure the import directory by creating a YAML config file in your project:

**app/_config/assets-sync.yml:**
```yaml
---
Name: my-assets-sync-config
After: webium-assets-sync
---
Webium\AssetsSync\Task\ImportFilesFromDirectoryTask:
  import_directory: 'my-custom-import-folder'
```

This will change the import source from `public/assets/import/` to `public/assets/my-custom-import-folder/`.

## How It Works

1. **Directory Scanning**: The task recursively scans the configured directory for all files
2. **Folder Creation**: Creates corresponding folders in the SilverStripe Assets system if they don't exist
3. **File Import**: For each file:
   - Checks if it already exists in the target folder
   - If it exists, skips it
   - If it's new, creates a new `File` object
   - Sets the file content using `setFromLocalFile()`
   - Assigns it to the correct parent folder
   - Writes it to the database
   - Publishes it
4. **Reporting**: Outputs the status of each file and final statistics

## Requirements

- PHP 7.4+ or 8.0+
- SilverStripe Framework ^4.0 or ^5.0
- SilverStripe Assets ^1.0 or ^2.0

## Use Cases

This module is particularly useful for:
- **Bulk Migration**: Moving large numbers of files from a file system into SilverStripe
- **Initial Setup**: Importing existing assets when setting up a new SilverStripe site
- **Content Sync**: Periodically syncing files from an external source
- **Development**: Quickly populating a development environment with assets

## Author

**Bart van Irsel**
Webium
Email: bart@webium.nl

## License

BSD-3-Clause

## Support

For issues and feature requests, please contact Webium or the module maintainer.