<?php namespace Sentinel\Repo\User;

use Mail;
use Cartalyst\Sentry\Sentry;
use Sentinel\Repo\RepoAbstract;
use Lang, Config;
use Cartalyst\Sentry\Users\UserNotActivatedException;

class SentryUser extends RepoAbstract implements UserInterface {
	
	protected $sentry;

	/**
	 * Construct a new SentryUser Object
	 */
	public function __construct(Sentry $sentry)
	{
		$this->sentry = $sentry;

		// Get the Throttle Provider
		$this->throttleProvider = $this->sentry->getThrottleProvider();

		// Enable the Throttling Feature
		$this->throttleProvider->enable();
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store($data)
	{
		$result = array();
		try {
			//Attempt to register the user. 
			$user = $this->sentry->register(array('email' => e($data['email']), 'password' => e($data['password'])));

			// Add the new user to the specified default group(s).
			$defaultUserGroups = Config::get('Sentinel::config.default_user_groups');

			foreach ($defaultUserGroups as $groupName) {
				$group = $this->sentry->getGroupProvider()->findByName($groupName);
				$user->addGroup($group);
			}

			//success!
	    	$result['success'] = true;
	    	$result['message'] = trans('Sentinel::users.created');
	    	$result['mailData']['activationCode'] = $user->GetActivationCode();
			$result['mailData']['userId'] = $user->getId();
			$result['mailData']['email'] = e($data['email']);
		}
		catch (\Cartalyst\Sentry\Users\LoginRequiredException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.loginreq');
		}
		catch (\Cartalyst\Sentry\Users\UserExistsException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.exists');
		}
		catch (Cartalyst\Sentry\Users\UserAlreadyActivatedException $e)
		{
		    $result['success'] = false;
            $result['message'] = trans('Sentinel::users.alreadyactive');
		}

		return $result;
	}
	
	/**
	 * Update the specified resource in storage.
	 *
	 * @param  array $data
	 * @return Response
	 */
	public function update($data)
	{
		$result = array();
		try
		{
		    // Find the user using the user id
		    $user = $this->sentry->findUserById($data['id']);

		    // Update the user details
		    $user->first_name = e($data['firstName']);
		    $user->last_name = e($data['lastName']);

		    // Only Admins should be able to change group memberships. 
		    $operator = $this->sentry->getUser();
		    if ($operator->hasAccess('admin'))
		    {
			    // Update group memberships
			    $allGroups = $this->sentry->getGroupProvider()->findAll();
			    foreach ($allGroups as $group)
			    {
			    	if (isset($data['groups'][$group->id])) 
	                {
	                    //The user should be added to this group
	                    $user->addGroup($group);
	                } else {
	                    // The user should be removed from this group
	                    $user->removeGroup($group);
	                }
			    }
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
		}
		catch (\Cartalyst\Sentry\Users\UserExistsException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.exists');
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}

		return $result;
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		try
		{
		    // Find the user using the user id
		    $user = $this->sentry->findUserById($id);

		    // Delete the user
		    $user->delete();
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    return false;
		}
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
		try
		{
		    // Find the user using the user id
		    $user = $this->sentry->findUserById($id);

		    // Attempt to activate the user
		    if ($user->attemptActivation($code))
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
		}
		catch (\Cartalyst\Sentry\Users\UserExistsException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.exists');
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}
		catch (\Cartalyst\Sentry\Users\UserAlreadyActivatedException $e)
		{
		    $result['success'] = false;
            $result['message'] = trans('Sentinel::users.alreadyactive');
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
		try {
            //Attempt to find the user. 
            $user = $this->sentry->getUserProvider()->findByLogin(e($data['email']));

            if (!$user->isActivated())
            {
#                $now = new \Datetime;
#                $throttle = $this->throttleProvider->findByUserId($user->getId());
#                $code_limit  = Config::get('Sentinel::config.resend_activation_code_limit');
#                $suspension_time = Config::get('Sentinel::config.resend_activation_code_suspension_time');
#                $last_resend = date_create($throttle->send_code_at);
#                $last_resend = $last_resend->modify("+{$suspension_time} minutes");
#
#                // timelapse limit reached, reset counter
#                if ($now >= $last_resend){
#                    $throttle->send_code_attempts = 0;
#                }
#
#                if ($code_limit <= $throttle->send_code_attempts){
#                    $result['success'] = false;
#                    $result['message'] = trans('Sentinel::users.resendcodelimit',array('minutes' => $suspension_time));
#                } else {
#                    $this->send_code_attempt($throttle);
#                }
                    //success!
                $result['success'] = true;
                $result['message'] = trans('Sentinel::users.emailconfirm');
                $result['mailData']['activationCode'] = $user->GetActivationCode();
                $result['mailData']['userId'] = $user->getId();
                $result['mailData']['email'] = e($data['email']);
            }
            else 
            {
                $result['success'] = false;
	    		$result['message'] = trans('Sentinel::users.alreadyactive');
            }

	    }
	    catch (\Cartalyst\Sentry\Users\UserExistsException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.exists');
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}
	    return $result;
	}

    protected function send_code_attempt($throttle)
    {
        $throttle->send_code_attempts++;
        $throttle->send_code_at = $throttle->freshTimeStamp();
        $throttle->save();
    }
	/**
	 * Handle a password reset rewuest
	 * @param  Array $data 
	 * @return Bool       
	 */
	public function forgotPassword($data)
	{
		$result = array();
		try
        {
			$user = $this->sentry->getUserProvider()->findByLogin(e($data['email']));
            if ( ! $user->isActivated()) {
                throw new UserNotActivatedException("Cannot reset password  user is not activated.");
            }
            $this->reset_password_attempts($user);
            $user->reset_code_created_at = new \Datetime; 

	        $result['success'] = true;
	    	$result['message'] = trans('Sentinel::users.emailinfo');
	    	$result['mailData']['resetCode'] = $user->getResetPasswordCode();
			$result['mailData']['userId'] = $user->getId();
			$result['mailData']['email'] = e($data['email']);
        }
        catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
		}
        catch (\Cartalyst\Sentry\Users\UserNotActivatedException $e)
        {
            $result['success'] = false;
            $url = route('Sentinel\resendActivationForm');
            $result['message'] = trans('Sentinel::sessions.notactive', array('url' => $url));
        }

        // The following is only required if throttle is enabled
        catch (\Cartalyst\Sentry\Throttling\UserSuspendedException $e)
        {
            $result['success'] = false;
            $result['message'] = trans('Sentinel::sessions.suspended');
        }
        catch (\Cartalyst\Sentry\Throttling\UserBannedException $e)
        {
            $result['success'] = false;
            $result['message'] = trans('Sentinel::sessions.banned');
        }
        return $result;
	}

    private function reset_password_attempts($user)
    {
        $throttle = $this->throttleProvider->findByUserId($user->id);
        $throttle->reset_attempts++;

        $firstAttempt = date_create($throttle->first_reset_attempt_at);
        $attemptWithIn = Config::get('Sentinel::config.reset_suspension_time');
        $validAttempt = $firstAttempt->modify("+{$attemptWithIn} minutes");
        $now             = new \Datetime;

        $attemptLimit = Config::get('Sentinel::config.reset_attempt_limit');
        
        if ($validAttempt >= $now) {
            # Allow to reset  
            #$throttle->check();
        }else {
            $throttle->reset_attempts = 1;
            $throttle->unsuspend();
        } 

        if ($throttle->reset_attempts > $attemptLimit && $validAttempt >= $now) {
            $throttle->suspend();
            throw new \Cartalyst\Sentry\Throttling\UserSuspendedException;
        } else {
            if ($validAttempt <= $now || empty($throttle->first_reset_attempt_at)) {
                $throttle->first_reset_attempt_at = $throttle->freshTimeStamp();
            }
            $throttle->save();
        }
    }

	/**
	 * Process the password reset request
	 * @param  int $id   
	 * @param  string $code 
	 * @return Array
	 */
	public function resetPassword($id, $code)
	{
		$result = array();
		try
        {
	        // Find the user
	        $user = $this->sentry->getUserProvider()->findById($id);
	        $newPassword = $this->_generatePassword(8,8);

			// Attempt to reset the user password
			if ($user->attemptResetPassword($code, $newPassword))
			{
				// Email the reset code to the user
	        	$result['success'] = true;
		    	$result['message'] = trans('Sentinel::users.emailpassword');
		    	$result['mailData']['newPassword'] = $newPassword;
		    	$result['mailData']['email'] = $user->getLogin();
 			}
			else
			{
				// Password reset failed
				$result['success'] = false;
				$result['message'] = trans('Sentinel::users.problem');
			}
        }
       catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
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
		try
		{
			$user = $this->sentry->getUserProvider()->findById($data['id']);        
		
			if ($user->checkHash(e($data['oldPassword']), $user->getPassword()))
			{
				//The oldPassword matches the current password in the DB. Proceed.
				$user->password = e($data['newPassword']);

				if ($user->save())
				{
					// User saved
					$result['success'] = true;
					$result['message'] = trans('Sentinel::users.passwordchg');
				}
				else
				{
					// User not saved
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
		}
		catch (\Cartalyst\Sentry\Users\LoginRequiredException $e)
		{
			$result['success'] = false;
			$result['message'] = 'Login field required.';
		}
		catch (\Cartalyst\Sentry\Users\UserExistsException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.exists');
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['success'] = false;
	    	$result['message'] = trans('Sentinel::users.notfound');
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

            Event::fire('sentinel.user.suspended', array(
                'userId' => $id, 
            ));

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
		try
		{
		    $user = $this->sentry->findUserById($id);
		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    return false;
		}
		return $user;
	}

	/**
	 * Return all the registered users
	 *
	 * @return stdObject Collection of users
	 */
	public function all()
	{
		$users = $this->sentry->findAllUsers();

		foreach ($users as $user) {
			if ($user->isActivated()) 
    		{
    			$user->status = "Active";
    		} 
    		else 
    		{
    			$user->status = "Not Active";
    		}

    		//Pull Suspension & Ban info for this user
    		$throttle = $this->throttleProvider->findByUserId($user->id);

    		//Check for suspension
    		if($throttle->isSuspended())
		    {
		        // User is Suspended
		        $user->status = "Suspended";
		    }

    		//Check for ban
		    if($throttle->isBanned())
		    {
		        // User is Banned
		        $user->status = "Banned";
		    }
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


	/**
     * Generate password - helper function
     * From http://www.phpscribble.com/i4xzZu/Generate-random-passwords-of-given-length-and-strength
     *
     */
    private function _generatePassword($length=9, $strength=4) {
        $vowels = 'aeiouy';
        $consonants = 'bcdfghjklmnpqrstvwxz';
        if ($strength & 1) {
               $consonants .= 'BCDFGHJKLMNPQRSTVWXZ';
        }
        if ($strength & 2) {
               $vowels .= "AEIOUY";
        }
        if ($strength & 4) {
               $consonants .= '23456789';
        }
        if ($strength & 8) {
               $consonants .= '@#$%';
        }

        $password = '';
        $alt = time() % 2;
        for ($i = 0; $i < $length; $i++) {
            if ($alt == 1) {
                $password .= $consonants[(rand() % strlen($consonants))];
                $alt = 0;
            } else {
                $password .= $vowels[(rand() % strlen($vowels))];
                $alt = 1;
            }
        }
        return $password;
    }
}
