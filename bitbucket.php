<?php
// Make sure we have a payload, stop if we do not.
if( ! isset( $_POST['payload'] ) )
	die( '<h1>No payload present</h1><p>A BitBucket POST payload is required to deploy from this script.</p>' );

/**
 * Tell the script this is an active end point.
 */
define( 'ACTIVE_DEPLOY_ENDPOINT', true );

require_once 'deploy-config.php';
/**
 * Deploys BitBucket git repos
 */
class BitBucket_Deploy extends Deploy {
	/**
	 * Decodes and validates the data from bitbucket and calls the 
	 * doploy contructor to deoploy the new code.
	 *
	 * @param 	string 	$payload 	The JSON encoded payload data.
	 */
	function __construct( $payload ) {
		$payload = json_decode( stripslashes( $_POST['payload'] ), true );
		$name = $payload['repository']['name'];
		
		$branch = '';
		
		foreach( $payload['commits'] as $commit ) {
			if ( strlen( $commit['branch'] ) > 0 ) {
				$branch = $commit['branch'];
				break;
			}
		}
		
		$this->log( 'Received push to ' . $name . ' on branch ' . $branch . ' (' . $payload['commits'][0]['raw_author'] . ') ' );
		
		if ( isset( parent::$repos[ $name ] ) /*&& parent::$repos[ $name ]['branch'] === $branch*/ ) {
			
			$data = parent::$repos[ $name ];
			$data['commit'] = $payload['commits'][0]['node'];
			parent::__construct( $name, $data );
			
		} else {

			// Dump the payload
			if (!file_exists('errors/')) mkdir('errors');

			$f = fopen('errors/' . date('Y-m-d-H-i-s') . '-' . str_replace(' ', '-', $name) . '.txt', 'w+');
			fwrite($f, print_r( $payload, true ));
			@fclose($f);
			
			$this->log( $name . ' update failed, branch missmatch ( ' . parent::$repos[ $name ]['branch'] . ' <> ' . $branch . ' ) ' );
			
		}
	}
}
// Start the deploy attempt.
new BitBucket_Deploy( $_POST['payload'] );
