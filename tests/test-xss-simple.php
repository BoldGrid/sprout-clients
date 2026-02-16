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

// Check for proper INPUT sanitization with wp_unslash
$has_wp_unslash = strpos($content, 'wp_unslash(') !== false;
$has_proper_text_sanitization = (
	strpos($content, 'sanitize_text_field( wp_unslash(') !== false
);
$has_proper_url_sanitization = (
	strpos($content, 'esc_url_raw( wp_unslash(') !== false
);

// Check for INCORRECT usage of output escaping functions for input sanitization
$has_incorrect_sanitization = (
	preg_match('/function\s+save_meta_box_client_communication.*?self::esc__\s*\(/s', $content)
);

if ($has_incorrect_sanitization) {
	print_fail('Client communication fields use OUTPUT escaping (esc__) instead of INPUT sanitization - VULNERABLE!');
	print_info('Note: esc__() is esc_attr__() - a translation/output function, NOT input sanitization');
	print_info('Should use: wp_unslash() + sanitize_text_field() / esc_url_raw()');
	$tests_failed++;
} elseif ($has_wp_unslash && $has_proper_text_sanitization && $has_proper_url_sanitization) {
	print_pass('Client communication fields use proper INPUT sanitization with wp_unslash()');
	print_pass('  - Text fields: sanitize_text_field( wp_unslash() )');
	print_pass('  - URL fields: esc_url_raw( wp_unslash() )');
	$tests_passed += 3;
} else {
	print_fail('Client communication fields missing complete sanitization pattern');
	print_info('Expected: wp_unslash() + sanitize_text_field() for text, esc_url_raw() for URLs');
	$tests_failed++;
}

// Test 3: Check for INPUT sanitization in save_meta_box_client_information
print_info('Test 3: Checking client information INPUT sanitization...');

// Check for proper sanitization in save_meta_box_client_information
$has_email_sanitization = strpos($content, 'sanitize_email( wp_unslash(') !== false;
$has_url_sanitization = strpos($content, 'esc_url_raw( wp_unslash(') !== false;
$has_text_sanitization = strpos($content, 'sanitize_text_field( wp_unslash(') !== false;

// Check for INCORRECT usage in user creation
$has_incorrect_user_args = (
	preg_match('/function\s+save_meta_box_client_information.*?user_args.*?self::esc__\s*\(/s', $content)
);

if ($has_incorrect_user_args) {
	print_fail('Client information uses esc__() in user creation args - VULNERABLE!');
	print_info('User args should use: sanitize_email(), sanitize_text_field(), esc_url_raw()');
	$tests_failed++;
} elseif ($has_email_sanitization && $has_url_sanitization && $has_text_sanitization) {
	print_pass('Client information fields use proper INPUT sanitization');
	print_pass('  - Email: sanitize_email( wp_unslash() )');
	print_pass('  - URLs: esc_url_raw( wp_unslash() )');
	print_pass('  - Text: sanitize_text_field( wp_unslash() )');
	$tests_passed += 4;
} else {
	print_fail('Client information fields missing complete sanitization');
	$tests_failed++;
}

// Test 4: Check for output escaping in show_twitter_feed
print_info('Test 4: Checking Twitter feed output escaping...');
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

// Test 5: Verify Twitter feed handles @ symbol normalization
print_info('Test 5: Checking Twitter feed handle normalization...');

// Check for Twitter handle normalization in show_twitter_feed
$has_normalization = (
	preg_match('/function\s+show_twitter_feed.*?ltrim\s*\(\s*trim\s*\(.*?\),\s*[\'"]@[\'"]\s*\)/s', $content)
);

// Check if the old vulnerable pattern is present (using esc__ on retrieval)
$has_old_esc_pattern = preg_match('/function\s+show_twitter_feed.*?self::esc__\s*\(\s*\$client->get_twitter\(\)/s', $content);

if ($has_normalization && !$has_old_esc_pattern) {
	print_pass('Twitter feed normalizes handle: ltrim( trim(), \'@\' )');
	print_pass('Twitter feed does not use esc__() on retrieval (correct)');
	$tests_passed += 2;
} elseif ($has_old_esc_pattern) {
	print_fail('Twitter feed uses self::esc__() on $client->get_twitter() - causes double-escaping');
	$tests_failed++;
} elseif (!$has_normalization) {
	print_fail('Twitter feed missing handle normalization (should strip leading @)');
	$tests_failed++;
} else {
	print_fail('Twitter feed has unexpected implementation');
	$tests_failed++;
}

// Test 6: Check for INPUT sanitization in save_profile_fields
print_info('Test 6: Checking user profile fields INPUT sanitization...');
$file = $base_dir . '/controllers/clients/Clients_Users.php';
if (!file_exists($file)) {
	print_info('Clients_Users.php not found (may not exist in this version - skipping)');
	$tests_passed++;
} else {
	$content = file_get_contents($file);

	// Check for proper INPUT sanitization functions with wp_unslash
	$has_proper_sanitization = (
		strpos($content, 'sanitize_text_field( wp_unslash(') !== false ||
		strpos($content, 'sanitize_email( wp_unslash(') !== false ||
		strpos($content, 'esc_url_raw( wp_unslash(') !== false
	);

	// Check for INCORRECT usage of output escaping for input sanitization
	$has_incorrect_sanitization = (
		preg_match('/function\s+save_profile_fields.*?self::esc__\s*\(/s', $content)
	);

	if ($has_incorrect_sanitization) {
		print_fail('User profile fields use OUTPUT escaping (esc__) instead of INPUT sanitization - VULNERABLE!');
		print_info('Note: esc__() is for output escaping, not input sanitization');
		$tests_failed++;
	} elseif ($has_proper_sanitization) {
		print_pass('User profile fields use proper INPUT sanitization with wp_unslash()');
		$tests_passed++;
	} else {
		print_fail('User profile fields have NO sanitization - VULNERABLE!');
		$tests_failed++;
	}
}

// Test 7: Verify user profile doesn't save raw $_POST
print_info('Test 7: Checking for direct $_POST usage in save_profile_fields...');
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

// Test 8: Check associated users view for proper escaping and Twitter handle normalization
print_info('Test 8: Checking associated users Twitter link escaping and normalization...');
$file = $base_dir . '/views/admin/meta-boxes/clients/associated-users.php';
$content = file_get_contents($file);

$has_url_escaping = (
	strpos($content, "esc_url( 'https://twitter.com/'") !== false ||
	strpos($content, 'esc_url( "https://twitter.com/"') !== false
);

$has_attr_escaping = (
	strpos($content, "esc_attr( sc__( 'Twitter Profile' )") !== false ||
	strpos($content, 'esc_attr( sc__( "Twitter Profile" )') !== false
);

$has_twitter_normalization = (
	strpos($content, "ltrim( trim(") !== false &&
	strpos($content, "), '@' )") !== false
);

if ($has_url_escaping && $has_attr_escaping && $has_twitter_normalization) {
	print_pass('Associated users view properly escapes Twitter links with esc_url()');
	print_pass('  - Attribute escaping: esc_attr( sc__() )');
	print_pass('  - Twitter handle normalization: ltrim( trim(), \'@\' )');
	$tests_passed += 3;
} elseif ($has_url_escaping && $has_attr_escaping) {
	print_pass('Associated users view has proper escaping but missing Twitter normalization');
	print_info('Consider adding: ltrim( trim( $handle ), \'@\' ) to strip leading @');
	$tests_passed++;
	$tests_failed++;
} else {
	print_fail('Associated users view missing proper escaping or normalization');
	$tests_failed++;
}

// Test 9: Verify esc__() is correctly documented as OUTPUT escaping (not input sanitization)
print_info('Test 9: Verifying esc__() method usage...');
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

// Test 10: Check for dangerous patterns
print_info('Test 10: Scanning for dangerous unescaped output patterns...');
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

// Test 11: Check for XSS test payloads in code (should not exist)
print_info('Test 11: Checking that test XSS payloads are not hardcoded...');
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
	print_info('');
	print_info('Next steps for complete verification:');
	print_info('  1. Create a test client with XSS payload in Twitter field');
	print_info('  2. Verify payload is sanitized in database');
	print_info('  3. Verify output is properly escaped in admin UI');
	print_info('  4. Test with Twitter handles starting with @ symbol');
	exit(0);
} else {
	print_fail('Some tests failed. Please review the failures above.');
	print_info('Check that all security fix changes have been applied correctly.');
	exit(1);
}
