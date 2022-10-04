<?php
namespace Depicter\Front;


use Averta\Core\Utility\Arr;


class Assets
{
	/**
	 * @var string
	 */
	private $baseAssetsUrl = '';

	/**
	 * @var string
	 */
	private $version = DEPICTER_VERSION;


	public function bootstrap()
	{
		$this->baseAssetsUrl = \Depicter::core()->assets()->getUrl();
	}

	/**
	 * Retrieves a list of styles based on given group
	 *
	 * @param string|array  $group   A group name or list of groups in array
	 *
	 * @return array
	 */
	public function getStyles( $group = 'common' )
	{
		$assets = $this->getStylesDictionary();
		return $this->getAssetFromList( $group, $assets );
	}

	/**
	 * Retrieves a list of scripts based on given group
	 *
	 * @param string|array  $group   A group name or list of groups in array
	 *
	 * @return array
	 */
	public function getScripts( $group = 'player' )
	{
		$assets = $this->getScriptsDictionary();
		return $this->getAssetFromList( $group, $assets );
	}

	/**
	 * Enqueues a list of styles based on given group
	 *
	 * @param string|array  $group   A group name or list of groups in array
	 *
	 * @return array
	 */
	public function enqueueStyles( $group = 'common' )
	{
		$styleUrls = $this->getStyles( $group );

		foreach ( $styleUrls as $styleId => $styleUrl ) {
			\Depicter::core()->assets()->enqueueStyle( $styleId, $styleUrl, [] );
		}

		do_action( 'depicter/after/enqueue/styles', $styleUrls );

		return $styleUrls;
	}

	/**
	 * Enqueues a list of scripts based on given group
	 *
	 * @param string|array  $group   A group name or list of groups in array
	 *
	 * @return array
	 */
	public function enqueueScripts( $group = 'player', $inFooter = true )
	{
		$scriptUrls = $this->getScripts( $group );

		foreach ( $scriptUrls as $scriptId => $scriptUrl ){
			\Depicter::core()->assets()->enqueueScript( $scriptId, $scriptUrl, [], $inFooter );
		}

		do_action( 'depicter/after/enqueue/scripts', $scriptUrls );

		return $scriptUrls;
	}

	/**
	 * Retrieves a list of custom styles for given slider IDs
	 *
	 * @param string|array $documentIDs  List of slider IDs for slugs
	 *
	 * @return array
	 */
	public function getCustomStyles( $documentIDs )
	{
		if( empty( $documentIDs ) ){
			return [];
		}

		if( $documentIDs = \Depicter::document()->getID( $documentIDs ) ){
			$documentIDs = (array) $documentIDs;
			$cssLinksToEnqueue = [];

			foreach( $documentIDs as $documentID ){
				if( false !== $cssLinkToEnqueue = \Depicter::cache('document')->get( $documentID . '_css_files' ) ){
					$cssLinksToEnqueue = $cssLinksToEnqueue + $cssLinkToEnqueue;
				}
			}

			return $cssLinksToEnqueue ;
		}

		return [];
	}

	/**
	 * Enqueues a list of custom styles based on given document IDs
	 *
	 * @param string|array $documentIDs  List of slider IDs for slugs
	 *
	 * @return array
	 */
	public function enqueueCustomStyles( $documentIDs )
	{
		$cssLinksToEnqueue = $this->getCustomStyles( $documentIDs );

		foreach( $cssLinksToEnqueue as $cssId => $cssLink ){
			\Depicter::core()->assets()->enqueueStyle( $cssId, $cssLink );
		}

		return $cssLinksToEnqueue;
	}

	/**
	 * Enqueues custom google fonts for given document IDs
	 *
	 * @param string|array $documentIDs  List of slider IDs for slugs
	 *
	 * @return array
	 */
	public function enqueueCustomGoogleFonts( $documentIDs )
	{
		$cssLinksToEnqueue = $this->getCustomStyles( $documentIDs );

		foreach( $cssLinksToEnqueue as $cssId => $cssLink ){
			if ( false !== strpos( $cssId, 'google-font' ) ) {
				\Depicter::core()->assets()->enqueueStyle( $cssId, $cssLink );
			}
		}

		return $cssLinksToEnqueue;
	}

	/**
	 * Enqueues custom styles of a document
	 *
	 * @param int    $documentID
	 * @param bool   $isPrivilegedUser
	 *
	 * @return void
	 */
	public function enqueueCustomAssets( $documentID, $isPrivilegedUser = null ){
		$isPrivilegedUser = is_null( $isPrivilegedUser ) ? \Depicter::authorization()->currentUserCanPublishDocument() : $isPrivilegedUser;

		if ( $isPrivilegedUser ) {
			$this->enqueueCustomGoogleFonts( $documentID );
		} else {
			$this->enqueueCustomStyles( $documentID );
		}
	}

	/**
	 * Enqueues styles and scripts of a document
	 *
	 * @param int    $documentID
	 * @param bool   $isPrivilegedUser
	 *
	 * @return void
	 */
	public function enqueueAssets( $documentID, $isPrivilegedUser = null ){
		$isPrivilegedUser = is_null( $isPrivilegedUser ) ? \Depicter::authorization()->currentUserCanPublishDocument() : $isPrivilegedUser;

		if ( ! did_action( 'depicter/after/enqueue/styles' ) ) {
			$this->enqueueStyles();
			$this->enqueueCustomAssets( $documentID, $isPrivilegedUser );
		}
		if ( ! did_action( 'depicter/after/enqueue/scripts' ) ) {
			$this->enqueueScripts();
		}
	}

	/**
	 * Searches for group(s) in list of given assets and returns result in a list
	 *
	 * @param string|array $group A group name or list of groups in array
	 * @param array        $assets
	 *
	 * @return array
	 */
	protected function getAssetFromList( $group = 'common', $assets = [] )
	{
		if( empty( $group ) ){
			return $assets;
		}

		if( is_array( $group ) ){
			if( count( $group ) > 1 ){
				$result = [];
				foreach ( $group as $groupKey ){
					if( $value = Arr::searchKeyDeep( $assets, $groupKey ) ){
						if( is_string( $value ) ){
							$result[ $groupKey ] = $value;
						} else {
							$result = $result + $value;
						}
					}
				}
				return $result;
			}
		}

		$group = (string) $group;
		$result = [];

		if( $value = Arr::searchKeyDeep( $assets, $group ) ){
			if( is_string( $value ) ){
				$result = [ $group => $value ];
			} elseif( is_array( $value ) ) {
				$result = $value;
			}
		}

		return $result;
	}

	/**
	 * Full list of front styles
	 *
	 * @return array
	 */
	protected function getStylesDictionary()
	{
		$stylesDictionary = [
			'situational' => [
				'minireset' => $this->baseAssetsUrl . '/resources/styles/frontend/reset.min.css',
				'depicter-preview-style' => $this->baseAssetsUrl . '/resources/styles/player/preview.css'
			],
			'common' => [
				'depicter--front-common' => $this->baseAssetsUrl . '/resources/styles/player/depicter.css'
			]
		];

		return $stylesDictionary;
	}

	/**
	 * Full list of front scripts
	 *
	 * @return array
	 */
	protected function getScriptsDictionary()
	{
		$scriptsDictionary = [
			'player' => [
				'depicter--player' => $this->baseAssetsUrl . '/resources/scripts/player/depicter.js'
			],
			'widget' => [
				'depicter-widget' => $this->baseAssetsUrl . '/resources/scripts/widgets/elementor-widget.js'
			]
		];

		return $scriptsDictionary;
	}

}
