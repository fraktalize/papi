<?php

/**
 * Check if `$obj` is a instanceof `Papi_Taxonomy_Type`.
 *
 * @param  mixed $obj
 *
 * @return bool
 */
function papi_is_taxonomy_type( $obj ) {
	return $obj instanceof Papi_Taxonomy_Type;
}

/**
 * Get taxonomy type id by term id.
 *
 * @param  int $term_id
 *
 * @return string
 */
function papi_get_taxonomy_type_id( $term_id = 0 ) {
	return papi_get_entry_type_id( $term_id, 'term' );
}

/**
 * Get taxonomy type by id.
 *
 * @param  string $id
 *
 * @return Papi_Taxonomy_Type
 */
function papi_get_taxonomy_type_by_id( $id ) {
	return papi_get_entry_type_by_id( $id );
}

/**
 * Load the entry type id on a taxonomy.
 *
 * @param  string $entry_type_id
 * @param  string $type
 *
 * @return string
 */
function papi_load_taxonomy_type_id( $entry_type_id = '', $type = 'term' ) {
	if ( $type !== 'term' ) {
		return $entry_type_id;
	}

	$key      = papi_get_page_type_key();
	$term_id  = papi_get_term_id();
	$taxonomy = papi_get_taxonomy( $term_id );

	// If we have a term id we can load the entry type id
	// from the term.
	if ( $term_id > 0 && papi_supports_term_meta() ) {
		$meta_value    = get_term_meta( $term_id, $key, true );
		$entry_type_id = empty( $meta_value ) ? '' : $meta_value;
	}

	// Load entry type id from the container if it exists.
	if ( empty( $entry_type_id ) ) {
		$key = sprintf( 'entry_type_id.taxonomy.%s', $taxonomy );

		if ( papi()->exists( $key )  ) {
			return papi()->make( $key );
		}
	}

	return $entry_type_id;
}

add_filter( 'papi/entry_type_id', 'papi_load_taxonomy_type_id', 10, 2 );