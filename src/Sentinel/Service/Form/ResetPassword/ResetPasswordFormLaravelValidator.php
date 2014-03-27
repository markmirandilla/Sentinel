<?php namespace Sentinel\Service\Form\ResetPassword;

use Sentinel\Service\Validation\AbstractLaravelValidator;

class ResetPasswordFormLaravelValidator extends AbstractLaravelValidator {
	
	/**
	 * Validation rules
	 *
	 * @var Array 
	 */
	protected $rules = array(
		'password' => 'required|min:6|confirmed',
		'password_confirmation' => 'required'
	);

}