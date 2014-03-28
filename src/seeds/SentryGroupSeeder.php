<?php

class SentryGroupSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		DB::table('groups')->delete();

		Sentry::getGroupRepository()->createModel()->create(array(
	        'name'        => 'Users',
	        'slug'		  => 'users',
	        'permissions' =>  array(
	            'users' => true,
	        )));

		Sentry::getGroupRepository()->createModel()->create(array(
	        'name'        => 'Admins',
	        'slug'		  => 'admins',
	        'permissions' => array(
	            'admin' => true,
	            'users' => true,
	        )));
	}

}