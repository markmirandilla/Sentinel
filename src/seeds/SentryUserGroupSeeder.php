<?php

class SentryUserGroupSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		DB::table('groups_users')->delete();

		$userUser = Sentry::findByCredentials(array('login' => 'user@user.com'));
		$adminUser = Sentry::findByCredentials(array('login' => 'admin@admin.com'));

		$userGroup = Sentry::getGroupRepository()->findBySlug('users');
		$adminGroup = Sentry::getGroupRepository()->findBySlug('admins');

	    // Assign the groups to the users
	    $userUser->groups()->attach($userGroup->getGroupId());
	    $adminUser->groups()->attach($userGroup->getGroupId());
	    $adminUser->groups()->attach($adminGroup->getGroupId());
	}

}