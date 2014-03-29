<?php namespace Sentinel\Repo\User;

use Mail;
use Cartalyst\Sentry\Sentry;
use Cartalyst\Sentry\Activations\IlluminateActivationRepository;
use Cartalyst\Sentry\Reminders\IlluminateReminderRepository;
use Sentinel\Repo\RepoAbstract;
use Lang;

class SentryUser extends RepoAbstract implements UserInterface {
	
	protected $sentry;
	protected $activation;
	protected $reminder;
	protected $user;

	/**
	 * Construct a new SentryUser Object
	 */
	public function __construct(Sentry $sentry, IlluminateActivationRepository $activation, IlluminateReminderRepository $reminder)
	{
		$this->sentry = $sentry;
		$this->activation = $activation;
		$this->reminder = $reminder;
		$this->user = $this->sentry->getUserRepository()->createModel();
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store($data)
	{
		$result = array();
		
		//Attempt to register the user. 
		$user = $this->sentry->register(array('email' => e($data['email']), 'password' => e($data['password'])));

		//success!
    	$result['success'] = true;
    	$result['message'] = trans('Sentinel::users.created');
    	$result['mailData']['activationCode'] = $this->activation->create($user)->code;
		$result['mailData']['userId'] = $user->id;
		$result['mailData']['email'] = $user->email;

		return $result;
	}
	
	/**
	 * Update the specified user in storage.
	 *
	 * @param  array $data
	 * @return Response
	 */
	public function update($data)
	{
		$result = array();

	    // Find the user using the user id
	    $user = $this->sentry->findById($data['id']);

	    // Update the user details
	    $user->first_name = e($data['firstName']);
	    $user->last_name = e($data['lastName']);

	    // Only Admins should be able to change group memberships. 
	    $operator = $this->sentry->getUser();
	    if ($operator->getPermissions()->hasAccess('admin'))
	    {
			// Update group memberships
	    	$user->groups()->sync(array_keys($data['groups']));
		}

	    // Update the user
	    if ($user->save())
	    {
	        // User information was updated
	        $result['success'] = true;
    		$result['message'] = trans('Sentinel::users.updated');
	    }
	    else
	    {
	        // User information was not updated
	        $result['success'] = false;
    		$result['message'] = trans('Sentinel::users.notupdated');
	    }

		return $result;
	}

	/**
	 * Remove the specified user from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
	    // Find the user using the user id
	    $user = $this->sentry->findById($id);

	    // Delete the user
	    $user->delete();
		
		return true;
	}

	/**
	 * Attempt activation for the specified user
	 * @param  int $id   
	 * @param  string $code 
	 * @return bool       
	 */
	public function activate($id, $code)
	{
		$result = array();
		
	    // Find the user using the user id
	    $user = $this->sentry->findById($id);

	    // Attempt to activate the user
	    if ($this->activation->complete($user, $code))
	    {
	        // User activation passed
	        $result['success'] = true;
	        $url = route('Sentinel\login');
    		$result['message'] = trans('Sentinel::users.activated', array('url' => $url));
	    }
	    else
	    {
	        // User activation failed
	        $result['success'] = false;
    		$result['message'] = trans('Sentinel::users.notactivated');
	    }
		
		return $result;
	}

	/**
	 * Resend the activation email to the specified email address
	 * @param  Array $data
	 * @return Response
	 */
	public function resend($data)
	{
		$result = array();
		
        //Attempt to find the user. 
        $user = $this->sentry->findByCredentials(array('login' => e($data['email'])));

        if (!$user->isActivated())
        {
        	// This user has not yet activated their account
        	$result['success'] = true;
    		$result['message'] = trans('Sentinel::users.emailconfirm');
    		$result['mailData']['activationCode'] = $this->activation->create($user)->code;
            $result['mailData']['userId'] = $user->id;
            $result['mailData']['email'] = $user->email;
        }
        else 
        {
            // This user has already activated their account. 
            $result['success'] = false;
    		$result['message'] = trans('Sentinel::users.alreadyactive');
        }

	    return $result;
	}

	/**
	 * Handle a password reset request
	 * @param  Array $data 
	 * @return Bool       
	 */
	public function forgotPassword($data)
	{
		$result = array();

		$user = $this->sentry->findByCredentials(array('login' => e($data['email'])));

        if ($user)
        {
        	// User Exists.  Send email with reset code. 
        	$result['success'] = true;
	    	$result['message'] = trans('Sentinel::users.emailinfo');
	    	$result['mailData']['resetCode'] = $this->reminder->create($user)->code;
			$result['mailData']['userId'] = $user->id;
			$result['mailData']['email'] = $user->email;
        }
        else 
        {
        	// User does not exist. 
        	$result['success'] = false;
        	$result['message'] = trans('Sentinel::users.notfound');
        }
        
        return $result;
	}

	/**
	 * Process the password reset request
	 * @param  Array $data - contains $id, $code and $password
	 * @return Array
	 */
	public function resetPassword($data)
	{
		$result = array();

        // Find the user
        $user = $this->sentry->findById($data['id']);

		// Attempt to reset the user password
		if ( ! $this->reminder->complete($user, $data['code'], $data['password']))
		{
			$result['success'] = false;
			$result['message'] = trans('Sentinel::users.passwordprob'); 
		}
		else 
		{
			$result['success'] = true;
	    	$result['message'] = trans('Sentinel::users.passwordchg');
		}
       
        return $result;
	}

	/**
	 * Process a change password request. 
	 * @return Array $data
	 */
	public function changePassword($data)
	{
		$result = array();

		// Find the user
    	$user = $this->sentry->findById($data['id']);  
		
		if ($this->sentry->getUserRepository()->validateCredentials($user, array('password' => e($data['oldPassword']))))
		{
			//The oldPassword matches the current password in the DB. Proceed.
			$credentials = array('login' => $user->email, 'password' => e($data['newPassword']));
			
			if ($this->sentry->getUserRepository()->validForUpdate($user, $credentials))
			{
				$this->sentry->getUserRepository()->update($user, $credentials);

				// User saved
				$result['success'] = true;
				$result['message'] = trans('Sentinel::users.passwordchg');
			}
			else
			{
				// There was a problem with the new password. 
				$result['success'] = false;
				$result['message'] = trans('Sentinel::users.passwordprob');
			}
		} 
		else 
		{
	        // Password mismatch. Abort.
	        $result['success'] = false;
			$result['message'] = trans('Sentinel::users.oldpassword');
		}                                        
		
		return $result;
	}

	/**
	 * Suspend a user
	 * @param  int $id      
	 * @param  int $minutes 
	 * @return Array          
	 */
	public function suspend($id, $minutes)
	{
		$result = array();
		try
		{
		    // Find the user using the user id
		    $throttle = $this->sentry->findThrottlerByUserId($id);

		    //Set suspension time
            $throttle->setSuspensionTime($minutes);

		    // Suspend the user
		    $throttle->suspend();

		    $result['success'] = true;
			$result['message'] = trans('Sentinel::users.suspended', array('minutes' => $minutes));
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}
		return $result;
	}

	/**
	 * Remove a users' suspension.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function unSuspend($id)
	{
		$result = array();
		try
		{
		    // Find the user using the user id
		    $throttle = $this->sentry->findThrottlerByUserId($id);

		    // Unsuspend the user
		    $throttle->unsuspend();

		    $result['success'] = true;
			$result['message'] = trans('Sentinel::users.unsuspended');
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}
		return $result;
	}

	/**
	 * Ban a user
	 * @param  int $id 
	 * @return Array     
	 */
	public function ban($id)
	{
		$result = array();
		try
		{
		    // Find the user using the user id
		    $throttle = $this->sentry->findThrottlerByUserId($id);

		    // Ban the user
		    $throttle->ban();

		    $result['success'] = true;
			$result['message'] = trans('Sentinel::users.banned');
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}
		return $result;
	}

	/**
	 * Remove a users' ban
	 * @param  int $id 
	 * @return Array     
	 */
	public function unBan($id)
	{
		$result = array();
		try
		{
		    // Find the user using the user id
		    $throttle = $this->sentry->findThrottlerByUserId($id);

		    // Unban the user
		    $throttle->unBan();

		    $result['success'] = true;
			$result['message'] = trans('Sentinel::users.unbanned');
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}
		return $result;
	}

	/**
	 * Return a specific user from the given id
	 * 
	 * @param  integer $id
	 * @return User
	 */
	public function byId($id)
	{
		return $this->user->find($id);
	}

	/**
	 * Return all the registered users
	 *
	 * @return stdObject Collection of users
	 */
	public function all()
	{
		$users = $this->sentry->getUserRepository()->createModel()->all();

		foreach ($users as $user) {
			if ($user->isActivated()) 
    		{
    			$user->status = "Active";
    		} 
    		else 
    		{
    			$user->status = "Not Active";
    		}

    		// //Pull Suspension & Ban info for this user
    		// $throttle = $this->throttleProvider->findByUserId($user->id);

    		// //Check for suspension
    		// if($throttle->isSuspended())
		    // {
		    //     // User is Suspended
		    //     $user->status = "Suspended";
		    // }

    		// //Check for ban
		    // if($throttle->isBanned())
		    // {
		    //     // User is Banned
		    //     $user->status = "Banned";
		    // }
		}

		return $users;
	}

	/**
	 * Provide a wrapper for Sentry::getUser()
	 *
	 * @return user object
	 */
	public function getUser()
	{
		return $this->sentry->getUser();
	}

}
