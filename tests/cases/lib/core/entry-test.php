<?php

class Papi_Lib_Core_Entry_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		$this->post_id = $this->factory->post->create();
	}

	public function tearDown() {
		parent::tearDown();
		unset( $this->post_id );
	}

	public function test_papi_get_entry_type_count() {
		$this->assertSame( 0, papi_get_entry_type_count( 'simple-page-type' ) );
		$this->assertSame( 0, papi_get_entry_type_count( null ) );
		$this->assertSame( 0, papi_get_entry_type_count( true ) );
		$this->assertSame( 0, papi_get_entry_type_count( false ) );
		$this->assertSame( 0, papi_get_entry_type_count( [] ) );
		$this->assertSame( 0, papi_get_entry_type_count( new stdClass() ) );
		$this->assertSame( 0, papi_get_entry_type_count( 1 ) );

		tests_add_filter( 'papi/settings/directories', function () {
			return [1,  PAPI_FIXTURE_DIR . '/page-types'];
		} );

		update_post_meta( $this->post_id, papi_get_page_type_key(), 'simple-page-type' );
		$this->assertSame( 1, papi_get_entry_type_count( 'simple-page-type' ) );

		$simple_page_type = papi_get_entry_type_by_id( 'simple-page-type' );

		$this->assertSame( 1, papi_get_entry_type_count( $simple_page_type ) );
	}

	public function test_papi_entry_type_exists() {
		tests_add_filter( 'papi/settings/directories', function () {
			return [1,  PAPI_FIXTURE_DIR . '/page-types'];
		} );

		$this->assertFalse( papi_entry_type_exists( 'hello' ) );
		$this->assertTrue( papi_entry_type_exists( 'empty-page-type' ) );
		$this->assertTrue( papi_entry_type_exists( 'options/header-option-type' ) );
	}

	public function test_papi_get_all_entry_types() {
		papi()->remove( 'papi_get_all_core_type_files' );

		$this->assertEmpty( papi_get_all_entry_types() );

		tests_add_filter( 'papi/settings/directories', function () {
			return [1,  PAPI_FIXTURE_DIR . '/entry-types'];
		} );

		papi()->remove( 'papi_get_all_core_type_files' );

		$this->assertNotEmpty( papi_get_all_entry_types() );

		$output = papi_get_all_entry_types( [
			'types' => 'entry'
		] );
		$this->assertNotEmpty( $output );
		$this->assertSame( 'Info entry type', $output[0]->name );
	}

	public function test_papi_get_all_entry_types_with_same_id() {
		tests_add_filter( 'papi/settings/directories', function () {
			return [PAPI_FIXTURE_DIR . '/entry-types', PAPI_FIXTURE_DIR . '/entry-types2'];
		} );

		papi()->remove( 'papi_get_all_core_type_files' );

		$output = papi_get_all_entry_types( [
			'types' => 'entry'
		] );

		$classes = array_map( 'get_class', array_values( $output ) );
		$this->assertTrue( in_array( 'Term_Entry_Type', $classes ) );
		$this->assertTrue( strpos( 'entry-types2/term-entry-type.php', $output[0]->get_file_path() ) !== -1 );
	}

	public function test_papi_get_all_entry_types_option() {
		tests_add_filter( 'papi/settings/directories', function () {
			return [1,  PAPI_FIXTURE_DIR . '/page-types'];
		} );

		papi()->remove( 'papi_get_all_core_type_files' );

		$output = papi_get_all_entry_types( [
			'types' => 'option'
		] );

		$this->assertNotEmpty( $output );
		$this->assertSame( 'Header', $output[0]->name );
	}

	public function test_papi_get_entry_type() {
		$this->assertNull( papi_get_entry_type( 'hello.php' ) );
		$path = PAPI_FIXTURE_DIR . '/page-types/boxes/simple.php';
		$this->assertNull( papi_get_entry_type( $path ) );
		$path = PAPI_FIXTURE_DIR . '/page-types/simple-page-type.php';
		$this->assertNotEmpty( papi_get_entry_type( $path ) );
		$path = PAPI_FIXTURE_DIR . '/page-types2/look-page-type.php';
		$page_type = papi_get_entry_type( $path );
		$this->assertTrue( $page_type instanceof Look_Module_Type );
	}

	public function test_papi_get_entry_type_by_id() {
		$this->assertNull( papi_get_entry_type_by_id( 0 ) );
		$this->assertNull( papi_get_entry_type_by_id( [] ) );
		$this->assertNull( papi_get_entry_type_by_id( (object) [] ) );
		$this->assertNull( papi_get_entry_type_by_id( true ) );
		$this->assertNull( papi_get_entry_type_by_id( false ) );
		$this->assertNull( papi_get_entry_type_by_id( null ) );
		$this->assertNull( papi_get_entry_type_by_id( 'page' ) );

		tests_add_filter( 'papi/settings/directories', function () {
			return [1,  PAPI_FIXTURE_DIR . '/page-types'];
		} );

		$simple_page_type = papi_get_entry_type_by_id( 'simple-page-type' );
		$this->assertTrue( is_object( $simple_page_type ) );
	}

	public function test_papi_get_entry_type_by_meta_id() {
		$this->assertNull( papi_get_entry_type_by_meta_id( 0 ) );
		$this->assertNull( papi_get_entry_type_by_meta_id( [] ) );
		$this->assertNull( papi_get_entry_type_by_meta_id( (object) [] ) );
		$this->assertNull( papi_get_entry_type_by_meta_id( true ) );
		$this->assertNull( papi_get_entry_type_by_meta_id( false ) );
		$this->assertNull( papi_get_entry_type_by_meta_id( null ) );

		tests_add_filter( 'papi/settings/directories', function () {
			return [1,  PAPI_FIXTURE_DIR . '/page-types'];
		} );

		update_post_meta( $this->post_id, papi_get_page_type_key(), 'simple-page-type' );

		$this->assertTrue( is_object( papi_get_entry_type_by_meta_id( $this->post_id ) ) );

		$_GET['page_id'] = $this->post_id;
		$this->assertTrue( is_object( papi_get_entry_type_by_meta_id() ) );
		unset( $_GET['page_id'] );
	}

	public function test_papi_get_entry_type_template() {
		$this->assertNull( papi_get_entry_type_template( 0 ) );

		tests_add_filter( 'papi/settings/directories', function () {
			return [1,  PAPI_FIXTURE_DIR . '/page-types',  PAPI_FIXTURE_DIR . '/taxonomy-types'];
		} );

		update_post_meta( $this->post_id, papi_get_page_type_key(), 'simple-page-type' );
		$this->assertSame( 'pages/simple-page.php', papi_get_entry_type_template( $this->post_id ) );

		if ( papi_supports_term_meta() ) {
			$term_id = $this->factory->term->create();
			update_term_meta( $term_id, papi_get_page_type_key(), 'simple-taxonomy-type' );
			$this->assertSame( 'pages/simple-taxonomy.php', papi_get_entry_type_template( $term_id, 'term' ) );
		}
	}

	public function test_papi_get_entry_type_id() {
		$_GET['entry_type'] = 'simple-page-type';
		$this->assertSame( 'simple-page-type', papi_get_entry_type_id() );
		unset( $_GET['entry_type'] );

		$post_id = $this->factory->post->create();
		$this->assertEmpty( papi_get_entry_type_id( $post_id ) );
	}
}