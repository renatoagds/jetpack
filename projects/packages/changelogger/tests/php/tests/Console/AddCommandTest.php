<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Tests for the changelogger add command.
 *
 * @package automattic/jetpack-changelogger
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

namespace Automattic\Jetpack\Changelogger\Tests\Console;

use Automattic\Jetpack\Changelogger\Utils;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Output\NullOutput;
use Wikimedia\TestingAccessWrapper;

/**
 * Tests for the changelogger add command.
 *
 * @covers \Automattic\Jetpack\Changelogger\Console\AddCommand
 */
class AddCommandTest extends CommandTestCase {
	use \Yoast\PHPUnitPolyfills\Polyfills\AssertionRenames;

	/**
	 * Set up.
	 *
	 * @before
	 */
	public function set_up() {
		parent::set_up();
		$this->useTempDir();
	}

	/**
	 * Test getDefaultFilename().
	 */
	public function testGetDefaultFilename() {
		$output = new NullOutput();
		$w      = TestingAccessWrapper::newFromObject( $this->getCommand( 'add' ) );

		// Test with no git checkout.
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}-\d{6}$/', $w->getDefaultFilename( $output ) );

		// Create a git checkout, master branch.
		$args = array( $output, new DebugFormatterHelper(), array( 'mustRun' => true ) );
		Utils::runCommand( array( 'git', 'init', '.' ), ...$args );
		Utils::runCommand( array( 'git', 'checkout', '-b', 'master' ), ...$args );
		Utils::runCommand( array( 'git', 'commit', '--author', 'Dummy <dummy@example.com>', '--allow-empty', '-m', 'Empty' ), ...$args );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}-\d{6}$/', $w->getDefaultFilename( $output ) );

		// Try a named branch.
		Utils::runCommand( array( 'git', 'checkout', '-b', 'test/default-filename' ), ...$args );
		$this->assertSame( 'test-default-filename', $w->getDefaultFilename( $output ) );
	}

	/**
	 * Test mkdir failure in execute().
	 */
	public function testExecute_mkdirFail() {
		file_put_contents( 'changelog', '' );
		$tester = $this->getTester( 'add' );
		$code   = $tester->execute( array() );
		$this->assertSame( 1, $code );
		$this->assertMatchesRegularExpression( '{^Could not create directory /.*/phpunit-changelogger-[0-9a-f]{6}/changelog: mkdir\(\): File exists\n$}', $tester->getDisplay() );
	}

	/**
	 * Test failure in execute() when file exists.
	 */
	public function testExecute_fileExists() {
		mkdir( 'changelog' );
		file_put_contents( 'changelog/testing', '' );
		$tester = $this->getTester( 'add' );
		$code   = $tester->execute( array( '--filename' => 'testing' ), array( 'interactive' => false ) );
		$this->assertSame( 1, $code );
		$this->assertMatchesRegularExpression( '{^File "/.*/phpunit-changelogger-[0-9a-f]{6}/changelog/testing" already exists. If you want to replace it, delete it manually.\n$}', $tester->getDisplay() );
	}

	/**
	 * Test the command.
	 *
	 * @dataProvider provideExecute
	 * @param string[]    $args Command line arguments.
	 * @param array       $options Options for CommandTester.
	 * @param string[]    $inputs User inputs.
	 * @param int         $expectExitCode Expected exit code.
	 * @param string|null $expectFile Expected change file contents, or null if no file should exist.
	 * @param string[]    $expectOutputRegexes Regexes to run against the output.
	 */
	public function testExecute( array $args, array $options, array $inputs, $expectExitCode, $expectFile, $expectOutputRegexes = array() ) {
		$tester = $this->getTester( 'add' );
		$tester->setInputs( $inputs );
		$code = $tester->execute( $args, $options );
		foreach ( $expectOutputRegexes as $re ) {
			$this->assertMatchesRegularExpression( $re, $tester->getDisplay() );
		}
		$this->assertSame( $expectExitCode, $code );
		$this->assertDirectoryExists( './changelog' );
		$files = glob( './changelog/*' );
		if ( null === $expectFile ) {
			$this->assertCount( 0, $files, 'No change file is expected' );
		} else {
			$this->assertCount( 1, $files, 'A change file is expected' );
			$this->assertSame( $expectFile, file_get_contents( $files[0] ) );
		}
	}

	/**
	 * Data provider for testExecute.
	 */
	public function provideExecute() {
		return array(
			'Normal interactive use'                      => array(
				array(),
				array(),
				array( '', 'patch', 'fixed', '', 'Testing.' ),
				0,
				"Significance: patch\nType: fixed\n\nTesting.\n",
			),
			'Normal interactive use with comment'         => array(
				array(),
				array(),
				array( '', 'patch', 'fixed', 'This is a comment', 'Testing.' ),
				0,
				"Significance: patch\nType: fixed\nComment: This is a comment\n\nTesting.\n",
			),
			'Normal interactive use with empty entry'     => array(
				array(),
				array(),
				array( '', 'patch', 'fixed', '', '' ),
				0,
				"Significance: patch\nType: fixed\n\n\n",
			),
			'Interactive use with command line defaults'  => array(
				array(
					'--significance' => 'patch',
					'--type'         => 'fixed',
					'--entry'        => 'Testing.',
				),
				array(),
				array( '', '', '', '', '' ),
				0,
				"Significance: patch\nType: fixed\n\nTesting.\n",
			),
			'Interactive use that runs into some errors'  => array(
				array(),
				array(),
				array(
					'<bad filename>',
					'bad:filename?',
					'bad/|\\filename',
					'.bad-ilename',
					'',
					'extreme',
					'minor',
					'improved',
					'fixed',
					'',
					'',
					'Testing.',
				),
				0,
				"Significance: minor\nType: fixed\n\nTesting.\n",
				array(
					'/Filename may not contain angle brackets/',
					'/Filename may not contain colons or question marks/',
					'/Filename may not contain slashes, backslashes, or pipes/',
					'/Filename may not begin with a dot/',
					'/Value "extreme" is invalid/',
					'/Value "improved" is invalid/',
					'/An empty changelog entry is only allowed when the significance is "patch"/',
				),
			),

			'Normal non-interactive use'                  => array(
				array(
					'--significance' => 'patch',
					'--type'         => 'fixed',
					'--entry'        => 'Testing.',
				),
				array( 'interactive' => false ),
				array(),
				0,
				"Significance: patch\nType: fixed\n\nTesting.\n",
			),
			'Normal non-interactive use with comment'     => array(
				array(
					'--significance' => 'patch',
					'--type'         => 'fixed',
					'--comment'      => 'This is a comment',
					'--entry'        => 'Testing.',
				),
				array( 'interactive' => false ),
				array(),
				0,
				"Significance: patch\nType: fixed\nComment: This is a comment\n\nTesting.\n",
			),
			'Normal non-interactive use with empty entry' => array(
				array(
					'--significance' => 'patch',
					'--type'         => 'fixed',
					'--entry'        => '',
				),
				array( 'interactive' => false ),
				array(),
				0,
				"Significance: patch\nType: fixed\n\n\n",
			),
			'Non-interactive use with empty filename'     => array(
				array(
					'--filename'     => '',
					'--significance' => 'patch',
					'--type'         => 'fixed',
					'--entry'        => '',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array( '/Filename may not be empty/' ),
			),
			'Non-interactive use with dot filename'       => array(
				array(
					'--filename'     => '.bad',
					'--significance' => 'patch',
					'--type'         => 'fixed',
					'--entry'        => '',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array( '/Filename may not begin with a dot/' ),
			),
			'Non-interactive use with missing significance' => array(
				array(
					'--type'  => 'fixed',
					'--entry' => 'Testing.',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array(
					'/Significance must be specified in non-interactive mode/',
				),
			),
			'Non-interactive use with invalid significance' => array(
				array(
					'--significance' => 'bogus',
					'--type'         => 'fixed',
					'--entry'        => 'Testing.',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array(
					'/Significance value "bogus" is not valid/',
				),
			),
			'Non-interactive use with missing type'       => array(
				array(
					'--significance' => 'patch',
					'--entry'        => 'Testing.',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array(
					'/Type must be specified in non-interactive mode/',
				),
			),
			'Non-interactive use with invalid type'       => array(
				array(
					'--significance' => 'patch',
					'--type'         => 'bogus',
					'--entry'        => 'Testing.',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array(
					'/Type "bogus" is not valid/',
				),
			),
			'Non-interactive use with missing entry'      => array(
				array(
					'--significance' => 'patch',
					'--type'         => 'fixed',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array(
					'/Entry must be specified in non-interactive mode/',
				),
			),
			'Non-interactive use with invalid entry'      => array(
				array(
					'--significance' => 'minor',
					'--type'         => 'fixed',
					'--entry'        => '',
				),
				array( 'interactive' => false ),
				array(),
				1,
				null,
				array(
					'/An empty changelog entry is only allowed when the significance is "patch"/',
				),
			),
		);
	}

}