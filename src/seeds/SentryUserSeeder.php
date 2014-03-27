<?php

class SentryUserSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		DB::table('users')->delete();

		Sentry::registerAndActivate(array(
	        'email'    => 'admin@admin.com',
	        'password' => 'sentryadmin',
	    ));

	    Sentry::registerAndActivate(array(
	        'email'    => 'user@user.com',
	        'password' => 'sentryuser',
	    ));
	}

}