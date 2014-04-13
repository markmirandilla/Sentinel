<?php namespace Sentinel\Repo\Session;

use Cartalyst\Sentry\Sentry;
use Sentinel\Repo\RepoAbstract;

class SentrySession extends RepoAbstract implements SessionInterface {

	protected $sentry;

	public function __construct(Sentry $sentry)
	{
		$this->sentry = $sentry;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store($data)
	{
		$result = array();
		try
			{
			    // Check for 'rememberMe' in POST data
			    if (!array_key_exists('rememberMe', $data)) $data['rememberMe'] = 0;

			    // Set login credentials
			    $credentials = array(
			        'email'    => e($data['email']),
			        'password' => e($data['password'])
			    );

			    // Try to authenticate the user
			    $user = false;
			    $user = $this->sentry->authenticate($credentials, e($data['rememberMe']));

			    if ($user) 
			    {
			    	// All is well. 
			    	$result['success'] = true;
				    $result['sessionData']['userId'] = $user->id;
				    $result['sessionData']['email'] = $user->email;
			    }
			    else 
			    {
			    	// User not found.
				    $result['success'] = false;
				    $result['message'] = trans('Sentinel::sessions.invalid');
			    }
			   
			}
			catch (\Cartalyst\Sentry\Checkpoints\NotActivatedException $e)
			{
			    $result['success'] = false;
			    $url = route('Sentinel\resendActivationForm');
			    $result['message'] = trans('Sentinel::sessions.notactive', array('url' => $url));
			}
			catch (\Cartalyst\Sentry\Checkpoints\ThrottlingException $e)
			{
			    $result['success'] = false;
			    $delay = $e->getDelay();
			    
			    switch ($e->getType()) 
			    {
			    	case 'global': 
			    		$result['message'] = trans('Sentinel::sessions.globalthrottle', array('delay' => $delay));
			    	break;

			    	case 'ip': 
			    		$result['message'] = trans('Sentinel::sessions.ipthrottle', array('delay' => $delay));
			    	break;

			    	case 'user':
			    		$result['message'] = trans('Sentinel::sessions.userthrottle', array('delay' => $delay));
			    	break;

			    	default:
			    		$result['message'] = trans('Sentinel::sessions.suspended');
			    	break;
			    }

			}
			// Need to add swipe identity exception


			//No exceptions were thrown. 
			return $result;
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy()
	{
		$this->sentry->logout();
	}


}