<?php
/**
 * Build script for Simple Waitlist for WooCommerce.
 *
 * Creates a production-ready zip in dist/ with only runtime files
 * and production Composer dependencies.
 *
 * Usage: php bin/build.php
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce\Build;

// Only allow CLI execution.
if ( 'cli' !== php_sapi_name() && ! defined( 'STDIN' ) ) {
	fwrite( STDERR, "This script must be run from the command line.\n" );
	exit( 1 );
}

$project_root = dirname( __DIR__ );
$dist_dir     = $project_root . '/dist';

/**
 * Extract the plugin version from the main plugin file header.
 *
 * @param string $plugin_file Main plugin file path.
 *
 * @return string
 */
function get_plugin_version( string $plugin_file ): string {
	$contents = file_get_contents( $plugin_file );
	if ( false === $contents ) {
		return '1.0.0';
	}

	if ( preg_match( '/Version:\s*([^\r\n]+)/i', $contents, $matches ) ) {
		return trim( $matches[1] );
	}

	return '1.0.0';
}

/**
 * Read .distignore patterns.
 *
 * @param string $project_root Project root path.
 *
 * @return array
 */
function get_distignore_patterns( string $project_root ): array {
	$file = $project_root . '/.distignore';
	if ( ! file_exists( $file ) ) {
		return [];
	}

	$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	if ( false === $lines ) {
		return [];
	}

	return array_values(
		array_filter(
			array_map( 'trim', $lines ),
			static function ( string $line ): bool {
				return '' !== $line && '#' !== $line[0];
			}
		)
	);
}

/**
 * Check if a path matches any .distignore pattern.
 *
 * @param string $relative_path Path relative to project root.
 * @param array  $patterns      Ignore patterns.
 *
 * @return bool
 */
function is_ignored( string $relative_path, array $patterns ): bool {
	$relative_path = str_replace( '\\', '/', $relative_path );

	foreach ( $patterns as $pattern ) {
		$pattern = str_replace( '\\', '/', $pattern );

		// Exact file or directory match.
		if ( $relative_path === $pattern || $relative_path === rtrim( $pattern, '/' ) ) {
			return true;
		}

		// Directory match anywhere in path.
		if ( '/' === substr( $pattern, -1 ) && false !== strpos( $relative_path, rtrim( $pattern, '/' ) . '/' ) ) {
			return true;
		}

		// Wildcard suffix (e.g. *.log).
		if ( '*' === $pattern[0] && fnmatch( $pattern, basename( $relative_path ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Remove files and directories matching .distignore patterns from a tree.
 *
 * @param string $dir      Directory to clean.
 * @param array  $patterns Ignore patterns.
 *
 * @return void
 */
function remove_ignored_files( string $dir, array $patterns ): void {
	$dir      = rtrim( str_replace( '\\', '/', $dir ), '/' );
	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
		\RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		$relative_path = str_replace( $dir . '/', '', str_replace( '\\', '/', $item->getPathname() ) );

		if ( is_ignored( $relative_path, $patterns ) ) {
			if ( $item->isDir() ) {
				recursive_rmdir( $item->getPathname() );
			} else {
				unlink( $item->getPathname() );
			}
		}
	}
}

/**
 * Recursively copy a directory.
 *
 * @param string $source Source directory.
 * @param string $dest   Destination directory.
 *
 * @return void
 */
function recursive_copy( string $source, string $dest ): void {
	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
		\RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $iterator as $item ) {
		$target = $dest . '/' . $iterator->getSubPathName();

		if ( $item->isDir() ) {
			if ( ! is_dir( $target ) ) {
				mkdir( $target, 0755, true );
			}
		} else {
			$target_dir = dirname( $target );
			if ( ! is_dir( $target_dir ) ) {
				mkdir( $target_dir, 0755, true );
			}
			copy( $item->getPathname(), $target );
		}
	}
}

/**
 * Recursively remove a directory.
 *
 * @param string $dir Directory path.
 *
 * @return void
 */
function recursive_rmdir( string $dir ): void {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
		\RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		if ( $item->isDir() ) {
			rmdir( $item->getPathname() );
		} else {
			unlink( $item->getPathname() );
		}
	}

	rmdir( $dir );
}

/**
 * Add a directory to a zip archive, honouring .distignore.
 *
 * @param \ZipArchive $zip          ZipArchive instance.
 * @param string      $source       Source directory.
 * @param string      $zip_prefix   Prefix inside the zip.
 * @param array       $ignore_patterns Patterns to ignore.
 *
 * @return void
 */
function add_directory_to_zip( \ZipArchive $zip, string $source, string $zip_prefix, array $ignore_patterns ): void {
	$source   = rtrim( str_replace( '\\', '/', $source ), '/' );
	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
		\RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $iterator as $item ) {
		$relative_path = str_replace( $source . '/', '', str_replace( '\\', '/', $item->getPathname() ) );

		if ( is_ignored( $relative_path, $ignore_patterns ) ) {
			continue;
		}

		$zip_path = $zip_prefix . '/' . $relative_path;

		if ( $item->isDir() ) {
			$zip->addEmptyDir( $zip_path );
		} else {
			$zip->addFile( $item->getPathname(), $zip_path );
		}
	}
}

$plugin_file = $project_root . '/simple-waitlist-for-woocommerce.php';
$version     = get_plugin_version( $plugin_file );
$zip_name    = "simple-waitlist-for-woocommerce-{$version}.zip";
$zip_path    = $dist_dir . '/' . $zip_name;

echo "Building Simple Waitlist for WooCommerce v{$version}...\n";

// Prepare dist directory.
if ( ! is_dir( $dist_dir ) ) {
	mkdir( $dist_dir, 0755, true );
}

// Create a temp working copy.
$temp_dir = sys_get_temp_dir() . '/simple-waitlist-build-' . uniqid();
mkdir( $temp_dir, 0755, true );

// Copy project files to temp.
echo "Copying project files...\n";
recursive_copy( $project_root, $temp_dir );

// Install production Composer dependencies in temp.
echo "Installing production dependencies...\n";
	$composer_command = sprintf(
		'composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --working-dir=%s 2>&1',
		escapeshellarg( $temp_dir )
	);
exec( $composer_command, $output, $return_code );

if ( 0 !== $return_code ) {
	fwrite( STDERR, "Composer install failed:\n" . implode( "\n", $output ) . "\n" );
	recursive_rmdir( $temp_dir );
	exit( 1 );
}

// Remove files excluded by .distignore from temp.
$ignore_patterns = get_distignore_patterns( $project_root );
remove_ignored_files( $temp_dir, $ignore_patterns );

// Create zip.
echo "Creating {$zip_name}...\n";

if ( class_exists( 'ZipArchive' ) ) {
	$zip = new \ZipArchive();
	if ( true !== $zip->open( $zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
		fwrite( STDERR, "Could not create zip archive at {$zip_path}\n" );
		recursive_rmdir( $temp_dir );
		exit( 1 );
	}

	add_directory_to_zip(
		$zip,
		$temp_dir,
		'simple-waitlist-for-woocommerce',
		$ignore_patterns
	);

	$zip->close();
} else {
	// Fallback to tar (BSD tar supports -a auto-detection from .zip extension).
	$tar_source = dirname( $temp_dir ) . '/simple-waitlist-for-woocommerce';
	rename( $temp_dir, $tar_source );
	$temp_dir = $tar_source;

	$tar_command = sprintf(
		'tar -acf %s -C %s %s 2>&1',
		escapeshellarg( $zip_path ),
		escapeshellarg( dirname( $temp_dir ) ),
		escapeshellarg( basename( $temp_dir ) )
	);
	exec( $tar_command, $tar_output, $tar_return_code );

	if ( 0 !== $tar_return_code ) {
		fwrite( STDERR, "Neither ZipArchive nor tar could create the archive.\n" . implode( "\n", $tar_output ) . "\n" );
		recursive_rmdir( $temp_dir );
		exit( 1 );
	}
}

recursive_rmdir( $temp_dir );

echo "Build complete: {$zip_path}\n";
exit( 0 );
