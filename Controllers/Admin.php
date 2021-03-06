<?php
namespace Beerlog\Controllers;

class Admin
{
	private $_pluginBaseDir = null;

	private static $_metaNonceAction 	= 'beerlog_edit_beer';
	private static $_metaNonceField 	= 'beerlog_meta_nonce';

	public function __construct()
	{
		$this->_pluginBaseDir = plugin_dir_path( dirname( __FILE__ ) );
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $postId The ID of the post being saved.
	 */
	public function savePostMeta( $postId )
	{
		if ( !self::assertEditPossible( $postId ) )
			return $postId;

		if ( isset( $_POST['beerlog_meta'] ) && is_array( $_POST['beerlog_meta'] ) )
		{
			foreach ( $_POST['beerlog_meta'] as $fieldName => $value )
			{
				update_post_meta( $postId, "_beerlog_meta_{$fieldName}", sanitize_text_field( $value ) );
			}

			if ( isset( $_POST['beerlog_meta']['prop_chart'] ) )
			{
				$fieldsArr = array();
				if ( 'simple' == $_POST['beerlog_meta']['prop_chart'] )
				{
					$fieldsArr = \Beerlog\Utils\Init::$propsSimple;
				}
				elseif ( 'pro' == $_POST['beerlog_meta']['prop_chart'] )
				{
					$fieldsArr = \Beerlog\Utils\Init::$propsPro;
				}

				foreach ( $fieldsArr as $propName => $value )
				{
					if ( isset( $_POST['beerlog_meta_props'][ $propName ] ) )
					{
						update_post_meta( $postId, "_beerlog_meta_props_{$propName}", (int) $_POST['beerlog_meta_props'][ $propName ] );
					}
				}
			}
		}
	}

	public static function assertEditPossible( $postId )
	{
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return false;

		// Verify that the nonce is pesent and valid.
		if ( !isset( $_POST[ self::$_metaNonceField ] )
			|| !wp_verify_nonce( $_POST[ self::$_metaNonceField ], self::$_metaNonceAction ) )
			return false;

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] )
		{
			if ( !current_user_can( 'edit_page', $postId ) )
				return false;
		}
		else
		{
			if ( !current_user_can( 'edit_post', $postId ) )
				return false;
		}

		return true;
	}

	public function addBeerPropertiesMeta()
	{
		global $post;

		// We'll use this nonce field later on when saving.
		wp_nonce_field( self::$_metaNonceAction, self::$_metaNonceField );

		echo $this->renderBeerPropertiesEdit( $post );
	}

	public function renderBeerPropertiesEdit( $post )
	{
		include $this->_pluginBaseDir . 'templates/admin/beer_properties.php';
	}
}