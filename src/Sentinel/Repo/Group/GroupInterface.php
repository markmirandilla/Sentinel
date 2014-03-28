<?php namespace Sentinel\Repo\Group;

interface GroupInterface {

	/**
	 * Store a newly created group.
	 *
	 * @return Response
	 */
	public function store($data);
	
	/**
	 * Update the specified group in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id);

	/**
	 * Remove the specified group from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id);

	/**
	 * Return a specific group by a given id
	 * 
	 * @param  integer $id
	 * @return User
	 */
	public function byId($id);

	/**
	 * Return a specific group by a given name
	 * 
	 * @param  string $name
	 * @return User
	 */
	public function byName($name);

	/**
	 * Return a specific group by a given slug
	 * 
	 * @param  string $name
	 * @return User
	 */
	public function bySlug($slug);

	/**
	 * Return all the groups
	 *
	 * @return stdObject Collection of groups
	 */
	public function all();

}
