<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\UpdateJob;

use Title;

/**
 * Tests for the UpdateJob class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\UpdateJob
 * @covers \SMW\JobBase
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class UpdateJobTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\UpdateJob';
	}

	/**
	 * Helper method that returns a UpdateJob object
	 *
	 * @since 1.9
	 *
	 * @return UpdateJob
	 */
	private function newInstance( Title $title = null, $settings = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		// Set smwgEnableUpdateJobs to false in order to avoid having jobs being
		// inserted as real jobs to the queue
		if ( $settings === null ) {
			$settings = $this->newSettings( array(
				'smwgCacheType'        => 'hash',
				'smwgEnableUpdateJobs' => false
			) );
		}

		$instance = new UpdateJob( $title );

		$builder = $instance->getDependencyBuilder();
		$container = $builder->getContainer();
		$container->registerObject( 'Settings', $settings );
		$container->registerObject( 'Store', $this->newMockBuilder()->newObject( 'Store' ) );

		// This seems redundant but it allows to cover
		// all necessary methods provided by the JobBase
		$instance->setDependencyBuilder( $builder );

		return $instance;

	}

	/**
	 * @test UpdateJob::__construct
	 *
	 * FIXME Delete SMWUpdateJob assertion after all references to
	 * SMWUpdateJob have been removed
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
		$this->assertInstanceOf( $this->getClass(), new \SMWUpdateJob( $this->newTitle() ) );
	}

	/**
	 * @test UpdateJob::__construct
	 *
	 * @since 1.9
	 */
	public function testRun() {

		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'exists' => true
		) );

		$this->assertFalse(
			$this->newInstance( $title )->run(),
			'asserts that the run() returns false due to a missing ParserOutput object'
		);

	}

	/**
	 * @test UpdateJob::run
	 * @dataProvider titleWikiPageDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunOnMockObjects( $setup, $expected ) {

		$instance  = $this->newInstance( $setup['title'] );

		$instance->getDependencyBuilder()
			->getContainer()
			->registerObject( 'ContentParser', $setup['contentParser'] );

		$this->assertEquals(
			$expected['result'],
			$instance->run(),
			'asserts run() in terms of the available ContentParser object'
		);
	}

	/**
	 * Provides title and wikiPage samples
	 *
	 * @return array
	 */
	public function titleWikiPageDataProvider() {

		$provider = array();

		// #0 Title does not exists, deleteSubject() is being executed
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey' => 'Lila',
			'exists'   => false
		) );

		$provider[] = array(
			array(
				'title'         => $title,
				'contentParser' => null
			),
			array(
				'result'        => true
			)
		);

		// #1 No revision, no further activities
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey' => 'Lala',
			'exists'   => true
		) );

		$contentParser = $this->newMockBuilder()->newObject( 'ContentParser', array(
			'getOutput' => null
		) );

		$provider[] = array(
			array(
				'title'         => $title,
				'contentParser' => $contentParser
			),
			array(
				'result'        => false
			)
		);

		// #2 Valid revision and parserOuput
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey' => 'Lula',
			'exists'   => true
		) );

		$contentParser = $this->newMockBuilder()->newObject( 'ContentParser', array(
			'getOutput' => $this->newMockBuilder()->newObject( 'ParserOutput' )
		) );

		$provider[] = array(
			array(
				'title'         => $title,
				'contentParser' => $contentParser
			),
			array(
				'result'        => true
			)
		);

		return $provider;
	}

}