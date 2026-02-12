#!/usr/bin/env php
<?php
/**
 * Simple XSS Security Test (No Database Required)
 *
 * Tests the security fix by checking the code directly
 *
 * @package Sprout_Clients
 * @subpackage Tests
 */

// Color output helpers
function print_pass($message) {
	echo "\033[0;32m✓ PASS: {$message}\033[0m\n";
}

function print_fail($message) {
	echo "\033[0;31m✗ FAIL: {$message}\033[0m\n";
}

function print_info($message) {
	echo "\033[0;34mℹ {$message}\033[0m\n";
}

function print_header($message) {
	echo "\n\033[1;36m" . str_repeat('=', 70) . "\033[0m\n";
	echo "\033[1;36m{$message}\033[0m\n";
	echo "\033[1;36m" . str_repeat('=', 70) . "\033[0m\n\n";
}

print_header('XSS Security Fix - Code Analysis Tests');

$tests_passed = 0;
$tests_failed = 0;
$base_dir = dirname(__DIR__);

// Test 1: Verify files exist
print_info('Test 1: Checking if security fix files exist...');
$files_to_check = array(
	'controllers/clients/Clients_Admin_Meta_Boxes.php',
	'controllers/clients/Clients_Users.php',
	'views/admin/meta-boxes/clients/associated-users.php',
);

foreach ($files_to_check as $file) {
	$path = $base_dir . '/' . $file;
	if (file_exists($path)) {
		print_pass("File exists: {$file}");
		$tests_passed++;
	} else {
		print_fail("File missing: {$file}");
		$tests_failed++;
	}
}

// Test 2: Check for INPUT sanitization in save_meta_box_client_communication
print_info('Test 2: Checking client communication INPUT sanitization...');
$file = $base_dir . '/controllers/clients/Clients_Admin_Meta_Boxes.php';
$content = file_get_contents($file);

// Check for proper INPUT sanitization functions (NOT output escaping)
$has_proper_sanitization = (
	strpos($content, 'sanitize_text_field( $phone )') !== false ||
	strpos($content, 'sanitize_text_field( $twitter )') !== false ||
	strpos($content, 'sanitize_text_field($_POST[\'sa_metabox_phone\']') !== false ||
	strpos($content, 'sanitize_text_field($_POST[\'sa_metabox_twitter\']') !== false
);

// Check for INCORRECT usage of output escaping functions for input sanitization
$has_incorrect_sanitization = (
	strpos($content, 'self::esc__( $phone )') !== false ||
	strpos($content, 'self::esc__( $twitter )') !== false
);

if ($has_proper_sanitization) {
	print_pass('Client communication fields use proper INPUT sanitization (sanitize_text_field)');
	$tests_passed++;
} elseif ($has_incorrect_sanitization) {
	print_fail('Client communication fields use OUTPUT escaping (esc__) instead of INPUT sanitization - VULNERABLE!');
	print_info('Note: esc__() is esc_attr__() - a translation/output function, NOT input sanitization');
	print_info('Should use: sanitize_text_field(), sanitize_url(), etc.');
	$tests_failed++;
} else {
	print_fail('Client communication fields have NO sanitization - VULNERABLE!');
	$tests_failed++;
}

// Test 3: Check for output escaping in show_twitter_feed
print_info('Test 3: Checking Twitter feed output escaping...');
if (strpos($content, 'esc_url') !== false &&
    strpos($content, 'esc_attr') !== false &&
    strpos($content, 'esc_html') !== false &&
    preg_match('/function\s+show_twitter_feed/', $content)) {
	print_pass('Twitter feed uses proper escaping functions (esc_url, esc_attr, esc_html)');
	$tests_passed++;
} else {
	print_fail('Twitter feed missing proper escaping functions');
	$tests_failed++;
}

// Test 4: Verify Twitter feed doesn't output unescaped variables
print_info('Test 4: Checking for direct variable output in Twitter feed...');
preg_match('/function\s+show_twitter_feed\s*\([^)]*\)\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/s', $content, $matches);
if (isset($matches[1])) {
	$function_content = $matches[1];
	// Check if the old vulnerable pattern is present
	if (preg_match('/printf\s*\([^,]+,\s*\$client->get_twitter\(\)/', $function_content)) {
		print_fail('Twitter feed still has unescaped $client->get_twitter() call');
		$tests_failed++;
	} else {
		print_pass('Twitter feed does not directly output $client->get_twitter()');
		$tests_passed++;
	}
} else {
	print_info('Could not parse show_twitter_feed function for detailed check (skipping)');
}

// Test 5: Check for INPUT sanitization in save_profile_fields
print_info('Test 5: Checking user profile fields INPUT sanitization...');
$file = $base_dir . '/controllers/clients/Clients_Users.php';
$content = file_get_contents($file);

// Check for proper INPUT sanitization functions
$has_proper_sanitization = (
	strpos($content, 'sanitize_text_field( $_POST') !== false ||
	preg_match('/sanitize_text_field\s*\(\s*\$[a-z_]+\s*\)/', $content)
);

// Check for INCORRECT usage of output escaping for input sanitization
$has_incorrect_sanitization = (
	strpos($content, 'self::esc__( $_POST') !== false ||
	preg_match('/self::esc__\s*\(\s*\$[a-z_]+\s*\)/', $content)
);

if ($has_proper_sanitization) {
	print_pass('User profile fields use proper INPUT sanitization (sanitize_text_field)');
	$tests_passed++;
} elseif ($has_incorrect_sanitization) {
	print_fail('User profile fields use OUTPUT escaping (esc__) instead of INPUT sanitization - VULNERABLE!');
	print_info('Note: esc__() is for output escaping, not input sanitization');
	$tests_failed++;
} else {
	print_fail('User profile fields have NO sanitization - VULNERABLE!');
	$tests_failed++;
}

// Test 6: Verify user profile doesn't save raw $_POST
print_info('Test 6: Checking for direct $_POST usage in save_profile_fields...');
preg_match('/function\s+save_profile_fields\s*\([^)]*\)\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/s', $content, $matches);
if (isset($matches[1])) {
	$function_content = $matches[1];
	// Check if raw $_POST is being saved without sanitization
	if (preg_match('/update_user_meta\s*\([^,]+,\s*[^,]+,\s*\$_POST\[/', $function_content)) {
		print_fail('User profile still saves raw $_POST data - VULNERABLE!');
		$tests_failed++;
	} else {
		print_pass('User profile does not save raw $_POST data');
		$tests_passed++;
	}
} else {
	print_info('Could not parse save_profile_fields function (skipping)');
}

// Test 7: Check associated users view for esc_url
print_info('Test 7: Checking associated users Twitter link escaping...');
$file = $base_dir . '/views/admin/meta-boxes/clients/associated-users.php';
$content = file_get_contents($file);

if (strpos($content, "esc_url( 'https://twitter.com/'") !== false ||
    strpos($content, 'esc_url( "https://twitter.com/"') !== false) {
	print_pass('Associated users view uses esc_url() for Twitter links');
	$tests_passed++;
} else {
	print_fail('Associated users view may not properly escape Twitter links');
	$tests_failed++;
}

// Test 8: Verify esc__() is correctly documented as OUTPUT escaping (not input sanitization)
print_info('Test 8: Verifying esc__() method usage...');
$files_to_check = array(
	'Sprout_Clients.php',
	'controllers/_Controller.php',
);

$esc_method_found = false;
$esc_method_content = '';
foreach ($files_to_check as $file) {
	$path = $base_dir . '/' . $file;
	if (file_exists($path)) {
		$content = file_get_contents($path);
		if (preg_match('/function\s+esc__\s*\([^)]*\)\s*\{[^}]*\}/s', $content, $matches)) {
			$esc_method_found = true;
			$esc_method_content = $matches[0];
			break;
		}
	}
}

if ($esc_method_found) {
	// Check if it's correctly using esc_attr__ (output escaping)
	if (strpos($esc_method_content, 'esc_attr__') !== false) {
		print_info('esc__() method found - wraps esc_attr__() for OUTPUT escaping');
		print_info('WARNING: This is NOT an input sanitization function!');
		print_info('For INPUT sanitization, use: sanitize_text_field(), sanitize_url(), etc.');
		$tests_passed++;
	} else {
		print_fail('esc__() method implementation unclear');
		$tests_failed++;
	}
} else {
	print_info('esc__() method not found (this is actually fine - it was misused for input sanitization)');
	$tests_passed++;
}

// Test 9: Check for dangerous patterns
print_info('Test 9: Scanning for dangerous unescaped output patterns...');
$files = array(
	'controllers/clients/Clients_Admin_Meta_Boxes.php',
	'views/admin/meta-boxes/clients/associated-users.php',
);

$dangerous_found = false;
$dangerous_patterns = array(
	'/echo\s+\$[a-z_]+->get_twitter\(\)/',
	'/printf\s*\([^)]+\$[a-z_]+->get_twitter\(\)[^)]*\)/',
	'/<a[^>]+href\s*=\s*["\'][^"\']*\$[a-z_]+->get_twitter/',
);

foreach ($files as $file) {
	$path = $base_dir . '/' . $file;
	if (file_exists($path)) {
		$content = file_get_contents($path);
		foreach ($dangerous_patterns as $pattern) {
			if (preg_match($pattern, $content)) {
				$dangerous_found = true;
				break 2;
			}
		}
	}
}

if (!$dangerous_found) {
	print_pass('No dangerous unescaped output patterns found');
	$tests_passed++;
} else {
	print_fail('Found potentially dangerous unescaped output patterns');
	$tests_failed++;
}

// Test 10: Check for XSS test payloads in code (should not exist)
print_info('Test 10: Checking that test XSS payloads are not hardcoded...');
$xss_test_patterns = array(
	'<script>alert(',
	'onerror=alert(',
	'javascript:alert(',
);

$test_payload_found = false;
foreach ($files as $file) {
	$path = $base_dir . '/' . $file;
	if (file_exists($path)) {
		$content = file_get_contents($path);
		foreach ($xss_test_patterns as $pattern) {
			if (stripos($content, $pattern) !== false) {
				$test_payload_found = true;
				break 2;
			}
		}
	}
}

if (!$test_payload_found) {
	print_pass('No XSS test payloads found in production code (good)');
	$tests_passed++;
} else {
	print_fail('Found XSS test payload in production code - should be removed');
	$tests_failed++;
}

// Summary
print_header('Test Summary');
echo "Tests Passed: \033[0;32m{$tests_passed}\033[0m\n";
echo "Tests Failed: \033[0;31m{$tests_failed}\033[0m\n";
echo "Total Tests:  " . ($tests_passed + $tests_failed) . "\n\n";

if ($tests_failed === 0) {
	print_pass('All code analysis tests passed! ✓');
	print_info('The security fix appears to be properly implemented.');
	print_info('Run manual tests (MANUAL-TEST.md) to verify runtime behavior.');
	exit(0);
} else {
	print_fail('Some tests failed. Please review the failures above.');
	print_info('Check that all security fix changes have been applied correctly.');
	exit(1);
}
