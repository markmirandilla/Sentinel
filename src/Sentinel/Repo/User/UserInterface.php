<?php namespace Sentinel\Repo\User;

interface UserInterface {

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store($data);
	
	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id);

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id);

	/**
	 * Attempt activation for the specified user
	 * @param  int $id   
	 * @param  string $code 
	 * @return bool       
	 */
	public function activate($id, $code);

	/**
	 * Resend the activation email to the specified email address
	 * @param  Array $data
	 * @return Response
	 */
	public function resend($data);

	/**
	 * Handle a password reset rewuest
	 * @param  Array $data 
	 * @return Bool       
	 */
	public function forgotPassword($data);

	/**
	 * Process the password reset request
	 * @param  int $id   
	 * @param  string $code 
	 * @return Array
	 */
	public function resetPassword($id, $code);

	/**
	 * Process a change password request. 
	 * @return Array $data
	 */
	public function changePassword($data);

	/**
	 * Suspend a user
	 * @param  int $id      
	 * @param  int $minutes 
	 * @return Array          
	 */
	public function suspend($id, $minutes);

	/**
	 * Remove a users' suspension.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function unSuspend($id);

	/**
	 * Ban a user
	 * @param  int $id 
	 * @return Array     
	 */
	public function ban($id);

	/**
	 * Remove a users' ban
	 * @param  int $id 
	 * @return Array     
	 */
	public function unBan($id);

	/**
	 * Return a specific user from the given id
	 * 
	 * @param  integer $id
	 * @return User
	 */
	public function byId($id);

	/**
	 * Return all the registered users
	 *
	 * @return stdObject Collection of users
	 */
	public function all();

	/**
	 * Provide a wrapper for Sentry::getUser()
	 *
	 * @return user object
	 */
	public function getUser();

}
