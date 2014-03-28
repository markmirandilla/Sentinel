<?php namespace Sentinel\Repo\Group;

use Cartalyst\Sentry\Sentry;
use Sentinel\Repo\RepoAbstract;
use Str;

class SentryGroup extends RepoAbstract implements GroupInterface {
	
	protected $sentry;
	protected $group;

	/**
	 * Construct a new SentryGroup Object
	 */
	public function __construct(Sentry $sentry)
	{
		$this->sentry = $sentry;
		$this->group = $this->sentry->getGroupRepository()->createModel();
	}

	/**
	 * Store a newly created group.
	 *
	 * @return Response
	 */
	public function store($data)
	{
		$result = array();

		// Convert $permissions array values to booleans
		foreach($data['permissions'] as &$val)
		{
			$val = (bool)$val;
		}
			    
	    // Create the group
	    $group = $this->group->create(
	    	array(
	        	'name'  	  => e($data['name']),
	        	'slug'		  => Str::slug(e($data['name'])),
	        	'permissions' => $data['permissions']
	        )
	    );

	   	$result['success'] = true;
		$result['message'] = trans('Sentinel::groups.created'); 

		return $result;
	}
	
	/**
	 * Update the specified group in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($data)
	{
		// Find the group using the group id
		$group = $this->group->find($data['id']);

		// Convert $permissions array values to booleans
		foreach($data['permissions'] as &$val)
		{
			$val = (bool)$val;
		}
		
	    // Update the group details
	    $group->name = e($data['name']);
	    $group->slug = Str::slug(e($data['slug']));
	  	$group->permissions = $data['permissions'];	

	    // Update the group
	    if ($group->save())
	    {
	        // Group information was updated
	        $result['success'] = true;
			$result['message'] = trans('Sentinel::groups.updated');;
	    }
	    else
	    {
	        // Group information was not updated
	        $result['success'] = false;
			$result['message'] = trans('Sentinel::groups.updateproblem');;
	    }

		return $result;
	}

	/**
	 * Remove the specified group from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
	    // Find the group using the group id
	    $group = $this->group->find($id);

	    // Delete the group
	    $group->delete();

		return true;
	}

	/**
	 * Return a specific group by a given id
	 * 
	 * @param  integer $id
	 * @return Group
	 */
	public function byId($id)
	{
		return $this->sentry->getGroupRepository()->findById($id);
	}

	/**
	 * Return a specific group by a given name
	 * 
	 * @param  string $name
	 * @return Group
	 */
	public function byName($name)
	{
		return $this->group->where('name', $name)->first();
	}

	/**
	 * Return a specific group by a given slug
	 * 
	 * @param  string $name
	 * @return Group
	 */
	public function bySlug($slug)
	{
		return $this->sentry->getGroupRepository()->findBySlug($slug);
	}

	/**
	 * Return all the registered groups
	 *
	 * @return stdObject Collection of groups
	 */
	public function all()
	{
		return $this->sentry->getGroupRepository()->createModel()->all();
	}
}
