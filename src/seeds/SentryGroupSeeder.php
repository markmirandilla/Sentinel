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
	            'admin' => 0,
	            'users' => 1,
	        )));

		Sentry::getGroupRepository()->createModel()->create(array(
	        'name'        => 'Admins',
	        'slug'		  => 'admins',
	        'permissions' => array(
	            'admin' => 1,
	            'users' => 1,
	        )));
	}

}