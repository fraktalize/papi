<?php

/**
 * Papi property functions.
 *
 * @package Papi
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Convert array of slugs to array with arrays in.
 *
 * @param array $value
 * @param string $slug
 *
 * @return array
 */

function papi_from_property_array_slugs( $values, $slug ) {
	$results = [];

	if ( empty( $values ) ) {
		return $results;
	}

	for ( $i = 0; $i < $values[$slug]; $i++ ) {
		$item      = [];
		$item_slug = $slug . '_' . $i . '_';
		$keys      = preg_grep( '/' . preg_quote( $item_slug ). '/' , array_keys( $values ) );

		foreach ( $keys as $key ) {
			$arr_key = str_replace( $item_slug, '', $key );
			$item[$arr_key] = $values[$key];
		}

		$results[] = $item;
	}

	return $results;
}

/**
 * Get box property.
 *
 * @param array $properties
 *
 * @return array
 */

function papi_get_box_property( $properties ) {
	$box_property = array_filter( $properties, function ( $property ) {
		return ! is_object( $property );
	} );

	if ( ! empty( $box_property ) && ! isset( $box_property[0] ) && ! isset( $box_property[0]['tab'] ) ) {
		$property = papi_get_property_options( $properties );

		if ( ! $property->disabled ) {
			$property->_box_property = true;
			$properties = [$property];
		}
	}
	return $properties;
}

/**
 * Get options and properties.
 *
 * @param string|array $file_or_options
 * @param array $properties
 * @param bool $is_box
 *
 * @return array
 */

function papi_get_options_and_properties( $file_or_options = [], $properties = [], $is_box = true ) {
	$options = [];

	if ( is_array( $file_or_options ) ) {
		if ( empty( $properties ) && $is_box ) {
			// Check if we have a title or not.
			if ( isset( $file_or_options['title'] ) ) {
				$options['title'] = $file_or_options['title'];
			} else if ( isset( $file_or_options[0]->title ) ) {
				$options['title'] = $file_or_options[0]->title;
				if ( $file_or_options[0]->sidebar === false && $file_or_options[0]->required ) {
					$options['_required'] = true;
				}
			} else if ( isset( $file_or_options[0]->options ) && isset( $file_or_options[0]->options['title'] ) ) {
				$options['title'] = $file_or_options[0]->options['title'];
			} else {
				$options['title'] = '';
			}
			$properties  = $file_or_options;
		} else {
			$options = array_merge( $options, $file_or_options );

			if ( ! $is_box ) {
				// Add all non string keys to the properties array
				foreach ( $options as $key => $value ) {
					if ( ! is_string( $key ) ) {
						$properties[] = $value;
						unset( $options[$key] );
					}
				}
			}
		}
	} else if ( is_string( $file_or_options ) ) {
		// If it's a template we need to load it the right way
		// and add all properties the right way.

		if ( papi_is_ext( $file_or_options, 'php' ) ) {
			$values = $properties;
			$template = papi_template( $file_or_options, $values );

			// Create the property array from existing property array or a new.
			$properties = [];
			$options = $template;

			// Add all non string keys to the properties array
			foreach ( $options as $key => $value ) {
				if ( ! is_string( $key ) ) {
					$properties[] = $value;
					unset( $options[$key] );
				}
			}
		} else {
			// The first parameter is used as the title.
			$options['title'] = $file_or_options;
		}
	}

	return [$options, $properties];
}

/**
 * Get default options.
 *
 * @return array
 */

function papi_get_property_default_options() {
	return Papi_Property::default_options();
}

/**
 * Get property type default settings
 *
 * @param string $type
 *
 * @return array
 */

function papi_get_property_default_settings( $type ) {
	$property_type = papi_get_property_type( $type );

	if ( is_null( $property_type ) || ! method_exists( $property_type, 'get_default_settings' ) ) {
		return [];
	}

	return $property_type->get_default_settings();
}

/**
 * Get property options.
 *
 * @param array $options
 * @param bool $fetch_value
 *
 * @return object
 */

function papi_get_property_options( $options, $fetch_value = true ) {
	if ( ! is_array( $options ) ) {
		if ( is_object( $options ) ) {
			return $options;
		}

		return;
	}

	$property = Papi_Property::create( $options );
	$options = $property->get_options();
	$options->value = $property->get_value( $fetch_value );

	return $options;
}

/**
 * Get property class name.
 *
 * @param string $type
 *
 * @return string
 */

function papi_get_property_class_name( $type ) {
	$type = papi_get_property_short_type( $type );

	if ( empty( $type ) ) {
		return;
	}

	return 'Papi_Property_' . ucfirst( $type );
}

/**
 * Get property short type.
 *
 * @param string $type
 *
 * @return string
 */

function papi_get_property_short_type( $type ) {
	if ( ! is_string( $type ) ) {
		return;
	}

	return preg_replace( '/^property/', '', strtolower( $type ) );
}

/**
 * Get property type by the given type.
 *
 * @param string $type
 *
 * @return null|Papi_Property
 */

function papi_get_property_type( $type ) {
	if ( is_object( $type ) && isset( $type->type ) && is_string( $type->type ) ) {
		$type = $type->type;
	}

	return Papi_Property::factory( $type );
}

/**
 * Get property type key from base64 string.
 *
 * @param string $str
 *
 * @return string
 */

function papi_get_property_type_from_base64( $str ) {
	if ( is_string( $str ) && preg_match( '/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str ) ) {
		$str = base64_decode( $str );
		$property = unserialize( $str );
		if ( is_object( $property ) ) {
			return $property->type;
		}
	}
}

/**
 * Get the right key for a property type.
 *
 * @param string $str
 * @param bool $papi_prefix
 *
 * @return string
 */

function papi_get_property_type_key( $str = '', $papi_prefix = false ) {
	$suffix = '_property';

	if ( ! is_string( $str ) ) {
		return $suffix;
	}

	$len = strlen( $str );

	if ( isset( $str[$len - 1] ) && $str[$len - 1] === ']' ) {
		$str = substr( $str, 0, $len - 1 );
		return papi_get_property_type_key( $str ) . ']';
	}

	if ( $papi_prefix ) {
		return $str . $suffix;
	}

	return papi_remove_papi( $str . $suffix );
}

/**
 * Get the right key for a property type with a underscore as the first character.
 *
 * @param string $str
 * @param bool $papi_prefix
 *
 * @return string
 */

function papi_get_property_type_key_f( $str, $papi_prefix = false ) {
	return papi_f( papi_get_property_type_key( $str, $papi_prefix ) );
}

/**
 * Check if it's ends with '_property'.
 *
 * @param string $str
 *
 * @return boolean
 */

function papi_is_property_type_key( $str = '' ) {
	$pattern = '_property';
	$pattern = str_replace( '_', '\_', $pattern );
	$pattern = str_replace( '-', '\-', $pattern );
	$pattern = '/' . $pattern . '$/';

	return preg_match( $pattern, $str ) === 1;
}

/**
 * Create a new property array or rendering a template property file.
 *
 * @param mixed $file_or_options
 * @param array $values
 *
 * @return object
 */

function papi_property( $file_or_options, $values = [] ) {
	if ( is_array( $file_or_options ) ) {
		return papi_get_property_options( $file_or_options );
	}

	if ( is_string( $file_or_options ) && is_array( $values ) ) {
		return (object) papi_template( $file_or_options, $values, true );
	}

	if ( is_object( $file_or_options ) ) {
		return $file_or_options;
	}
}

/**
 * Render a property the right way.
 *
 * @param object $property
 */

function papi_render_property( $property ) {
	// Check so type isn't empty and capabilities on the property.
	if ( empty( $property->type ) || ! papi_current_user_is_allowed( $property->capabilities ) ) {
		return;
	}

	$property_type = papi_get_property_type( $property->type );

	if ( is_null( $property_type ) ) {
		return;
	}

	$property_type->set_options( $property );

	// Only render if it's the right language if the definition exist.
	if ( $property->lang !== false && papi_get_qs( 'lang' ) != null ) {
		$render = $property->lang === strtolower( papi_get_qs( 'lang' ) );
	} else {
		$render = true;
	}

	// Render the property.
	if ( $render && $property->disabled === false ) {
		$property_type->render_row_html();
		$property_type->render_hidden_html();
	}
}

/**
 * Render properties the right way.
 *
 * @param array $properties
 */

function papi_render_properties( $properties ) {
	// Don't proceed without any properties
	if ( ! is_array( $properties ) || empty( $properties ) ) {
		return;
	}

	// If it's a tab the tabs class will
	// handle the rendering of the properties.

	if ( isset( $properties[0]->tab ) && $properties[0]->tab ) {
		new Papi_Admin_Meta_Box_Tabs( $properties );
	} else {
		?>

		<table class="papi-table">
			<tbody>
			<?php
			foreach ( $properties as $property ) {
				papi_render_property( $property );
			}
			?>
			</tbody>
		</table>

	<?php
	}
}

/**
 * Get require text for property.
 *
 * @param object $property
 *
 * @return string
 */

function papi_require_text( $property ) {
	if ( ! is_object( $property ) || ! $property->required ) {
		return '';
	}

	return esc_html__( '(required field)', 'papi' );
}

/**
 * Get require tag for property.
 *
 * @param object $property
 * @param bool $text
 *
 * @return string
 */

function papi_required_html( $property, $text = false ) {
	if ( ! is_object( $property ) || ! $property->required ) {
		return '';
	}

	return ' <span class="papi-rq" data-property-name="' . $property->title . '" data-property-id="' . $property->slug . '">' . ( $text ? papi_require_text( $property ) : '*' ) . '</span>';
}

/**
 * Populate properties array.
 *
 * @param array|object $properties
 *
 * @return array
 */

function papi_populate_properties( $properties ) {
	// If $properties is a object we can just return it in a array.
	if ( is_object( $properties )  ) {
		return [$properties];
	}

	$results = [];

	// Get the box property (when you only put a array in the box method) if it exists.
	$properties = papi_get_box_property( $properties );

	// Convert all non property objects to property objects.
	$properties = array_map( function ( $property ) {
		if ( ! is_object( $property ) && is_array( $property ) && ! isset( $property['tab'] ) ) {
			return papi_get_property_options( $property );
		}

		return $property;
	}, $properties );

	// Fix so the properties array will have the right order.
	$properties = array_reverse( $properties );

	foreach ( $properties as $property ) {
		if ( isset( $property->tab ) && $property->tab ) {
			$results[] = $property;
			continue;
		}

		$results[] = $property;
	}

	if ( empty( $results ) || ( isset( $results[0]->tab ) && $results[0]->tab ) ) {
		return $results;
	}

	return papi_sort_order( $results );
}

/**
 * Update property values on the post with the given post id
 * or update property values on the option page.
 *
 * @param array $meta
 */

function papi_property_update_meta( $meta ) {
	$meta   = (object) $meta;

	if ( empty( $meta->type ) ) {
		return;
	}

	$option     = papi_is_option_page();
	$save_value = true;

	foreach ( papi_to_array( $meta->value ) as $key => $value ) {
		if ( is_string( $key ) ) {
			$save_value = false;
			break;
		}
	}

	if ( ! $save_value && is_array( $meta->value ) ) {
		$meta->value = [$meta->value];
	}

	if ( papi_is_empty( $meta->value ) ) {
		if ( $option ) {
			delete_option( $meta->slug );
		} else {
			delete_post_meta( $meta->post_id, papi_remove_papi( $meta->slug ) );
		}
		return;
	}

	foreach ( papi_to_array( $meta->value ) as $key => $value ) {
		if ( ! is_array( $value ) ) {
			if ( $save_value ) {
				$value = $meta->value;
			}

			if ( $option ) {
				update_option( $meta->slug, $value );
			} else {
				update_post_meta( $meta->post_id, papi_remove_papi( $meta->slug ), $value );
			}

			continue;
		}

		foreach ( $value as $child_key => $child_value ) {
			if ( $option ) {
				if ( papi_is_property_type_key( $child_key ) ) {
					$child_key = papi_f( papify( $child_key ) );
				} else {
					$child_key = papify( $child_key );
				}

				update_option( $child_key, $child_value );
			} else {
				update_post_meta( $meta->post_id, papi_remove_papi( $child_key ), $child_value );
			}
		}
	}

	if ( $option ) {
		update_option( papi_get_property_type_key_f( $meta->slug, true ), $meta->type );
	} else {
		update_post_meta( $meta->post_id, papi_get_property_type_key_f( $meta->slug ), $meta->type );
	}
}

/**
 * Convert array of arrays to array of slugs.
 * The given slug will match a key with the number of properties.
 *
 * @param array $value
 * @param string $slug
 *
 * @return array
 */

function papi_to_property_array_slugs( $value, $slug ) {
	$results = [];
	$counter = [];

	foreach ( $value as $index => $arr ) {

		if ( ! is_array( $arr ) ) {
			continue;
		}

		$counter[] = $arr;

		foreach ( $arr as $key => $val ) {
			$item_slug = $slug . '_' . $index . '_' . $key;

			if ( papi_is_property_type_key( $item_slug ) ) {
				$item_slug = papi_f( $item_slug );
			}

			$results[$item_slug] = $val;
		}
	}

	$results[$slug] = count( $counter );

	return $results;
}
