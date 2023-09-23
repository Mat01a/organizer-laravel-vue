<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Projects_users;
use App\Models\Project;
use App\Models\User;

class ProjectController extends Controller
{
    //Get all projects for requested user
    public function index(Request $request)
    {
        $user_request_id = $request->user()->id;
        $user_all_projects_id = Projects_users::select('project_id')
            ->where('user_id', '=', $user_request_id)->get();
        if(count($user_all_projects_id) > 0)
        {
            //Get all projects
            $projects = Project::whereIn('id', $user_all_projects_id)->paginate(10);
    
            return response($projects, 200);
        }
        
        return response(["You don't have any projects"], 200);
    }

    //Create a new project 
    public function store(Request $request)
    {
        $user_request_id = $request->user()->id;
        $request->validate([
            'name' => 'required|min:8|max:255'
        ]);
        DB::beginTransaction();

        try {
            $number_of_projects = Project::select('id')->count();
            if($number_of_projects > 0)
            {
                $last_project_id = Project::select('id')->get()->last()->id;
            }
            else {
                $last_project_id = 0;
            }


            //Creating project
            $project = new Project;
            $project->name = $request->name;
            $project->status = 0;
            $project->save();
        
            //Creating new admin permission
            $admin_permission = new Permission;
            $admin_permission->project_id = $last_project_id+1;
            $admin_permission->name = 'admin';
            $admin_permission->read = 1;
            $admin_permission->write = 1;
            $admin_permission->updateName = 1;
            $admin_permission->addUsers = 1;
            $admin_permission->removeUsers = 1;
            $admin_permission->updateStatus = 1;
            $admin_permission->updatePermissions = 1;
            $admin_permission->save();

            //Creating new regular permission
            $regular = new Permission;
            $regular->project_id = $last_project_id+1;
            $regular->name = 'regular';
            $regular->read = 1;
            $regular->write = 1;
            $regular->updateName = 0;
            $regular->addUsers = 0;
            $regular->removeUsers = 0;
            $regular->updateStatus = 0;
            $regular->updatePermissions = 0;
            $regular->save();
    
            $project_user = new Projects_users;
            
            //creating admin user
            $project_user->user_id = $user_request_id;
            $project_user->project_id = $last_project_id+1;
            $project_user->permission_id = $admin_permission->id;
            $project_user->save();
            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
            error_log($th);
            DB::rollBack();
        }

        //return response(200, "New project has been created");
    }

    private function projectDifference($column, $data, $operator = '=')
    {
        $difference = Project::where($column, $operator, $data)->get();

        // If there's difference returns 1, or if there is not, returns 0.
        return count($difference);
    }


    // Changing project name
    public function update(Request $request)
    {
        $user = $request->user();
        $project_changes = json_decode($request->getContent());
        $changed_name = $project_changes->name;

        $check_permissions = $this->checkUserPermissionByUserID($user->id, $request->id);
        $permissions = $check_permissions[0]->updateName;

        if(!$permissions)
        {
            return response("You don't have permissions to do that.", 400);
        }


        // Changes name if there's difference between current one
        if(!$this->projectDifference('name', $changed_name))
        {
            try {
                # Updating name
                Project::where('id', $project_changes->id)->update(['name' => $changed_name]);
                error_log("Project name has been updated!");
                return response('Project name has been updated!', 200);
            } catch (\Throwable $th) {
                error_log($th);
            }

        }

    }


    public function findProposedUsers($id, $username)
    {
        $users_projects = Permission::select('user_id')
        ->where('project_id', '=', $id)->get();
        
        $search_username = $username.'%';
        $data = User::select('name','username')
        ->where('username', 'like', $search_username)
        ->whereNotIn('id', $users_projects)
        ->limit(5)
        ->get();
        return response($data, 200);
    }


    // Add user
    public function addUser(Request $request)
    {
        $current_user = $request->user();
        //Check if this user is able to add new users
        $permission = DB::table('permissions')
                        ->join('projects_users', 'permissions.id', '=', 'projects_users.permission_id')
                        ->where('permissions.addUsers', '=', 1)
                        ->where('projects_users.user_id', '=', $current_user->id)
                        ->get();
        if(!count($permission))
        {
            return response("You don't have permissions for this action.", 400);
        }
        try {

            $requested_user = User::where('username', '=', $request->username)->first();
            if($requested_user)
            {
                $requested_user_permissions = Projects_users::where('user_id', '=', $requested_user->id)->get();
            }
            else {
                return response("There is no a user with that username.", 400);
            }

            foreach ($requested_user_permissions as $key => $value) {
                # code...
                if($value->project_id === $request->id)
                {
                    return response("User is in current project.", 400);
                }
            }            

            $requested_username = $request->username;
            $user_to_be_added = User::where('username', '=',  $requested_username)
            ->get();
            DB::beginTransaction();
            try {
                $permissions = new Projects_users;

                //giving regular permissions
                $permissions->user_id = $user_to_be_added[0]->id;
                $permissions->project_id = $request->id;
                $permissions->permission_id = 2;
                $permissions->save();
                DB::commit();
                return response()->json(['success' => true, 'name' => $user_to_be_added[0]->name, 'username' => $user_to_be_added[0]->username]);
            } catch (\Throwable $th) {
                throw $th;
                error_log($th);
                DB::rollBack();
            }
        } catch (\Throwable $th) {
            throw $th;
            error_log($th);
        }
    }
    

    public function getUsersInProject(Request $request, $id)
    {
        try {
            $test_users = DB::table('users')
                        ->join('projects_users', 'users.id', '=', 'projects_users.user_id')
                        ->join('permissions', 'projects_users.permission_id', '=', 'permissions.id')
                        ->where('projects_users.project_id', '=', $id)
                        ->select('users.name', 'users.username', 'permissions.name AS permissions_name')
                        ->paginate(4);
            return response($test_users, 200);
        } catch (\Throwable $th) {
            throw $th;
            return response($th);
        }
    }

    public function updateProjectStatus(Request $request)
    {
        $validation = $request->validate([
            'project_id' => 'required',
            'status' => 'required|between:0,1'
        ]);
        $check_permissions = $this->checkUserPermissionByUserID($request->user()->id, $request->project_id);
        $permissions = $check_permissions[0]->updateStatus;

        if(!$permissions)
        {
            return response("You don't have permissions to do that.", 400);
        }

        DB::beginTransaction();
        try {
            $project = Project::where('id', '=', $request->project_id)->update(['status' => $request->status]);
            DB::commit();
            return response('Project status, has been changed!', 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    // User leaves project
    public function leaveProject(Request $request)
    {
        $validated = $request->validate(['project_id']);
        $user = $request->user();
        $project_id = $request->id;

        $project_users = Permission::where('project_id', '=', $project_id)
        ->get();

        if(count($project_users) > 1)
        {
            $project_admins = Permission::where('project_id', '=', $project_id)
            ->where('name', '=', 'admin')
            ->where('read', '=', 1)
            ->where('write', '=', 1)
            ->where('updatePermissions', '=', 1)
            ->where('user_id', 'IS NOT', $user->id)
            ->get();
            if(count($project_admins) > 0)
            {
                $user = Projects_users::where('user_id', '=', $user->id)
                        ->where('project_id', '=', $project_id);
                $user->delete();
                return response("You successfully left project", 200);
            }
            else {
                return response("You need have another admin in project to leave.", 400);
            }
        }
        else {
            return response("You need have two or more users to leave project.", 400);
        }
    }

    public function getPermissionsInProject(Request $request, $id)
    {
        try {
            // If user is in project he wants to get permissions
            $user_id = $request->user()->id;
            $validation = Projects_users::where('user_id', '=', $user_id)
                ->where('project_id', '=', $id)
                ->get();
            
            if($validation)
            {
                $permissions_in_project = Permission::where('project_id', '=', $id)->paginate(4);
                return response($permissions_in_project, 200);
            }
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['message' => "There's some error occur.", 'status' => 400]);
        }
    }

    public function addPermission(Request $request)
    {
        // If user is capable of adding permissions
        $user_id = $request->user()->id;
        $check_permissions = $this->checkUserPermissionByUserID($user_id);
        $permissions = $check_permissions[0]->updatePermissions;
        
        if(!$permissions)
        {
            return response("You don't have permissions to do that.", 400);
        }

        $request->validate([
            'project_id' => 'required|numeric',
            'name' => 'required|max:9'
        ]);
        try {
            DB::beginTransaction();
            $lowercase_name = strtolower($request->name);
            $new_permission = new Permission;
            $new_permission->project_id = $request->project_id;
            $new_permission->name = $lowercase_name;
            $new_permission->read = 0;
            $new_permission->write = 0;
            $new_permission->updateName = 0;
            $new_permission->addUsers = 0;
            $new_permission->removeUsers = 0;
            $new_permission->updateStatus = 0;
            $new_permission->updatePermissions = 0;
            $new_permission->save();
            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function removePermission(Request $request)
    {
        $user_id = $request->user()->id;
        $request->validate([
            'project_id' => 'required|numeric',
            'permission_id' => 'required|numeric'
        ]);

        $default_permissions_id = Permission::where('project_id', '=', $request->project_id)
        ->select('id')
        ->take(2)
        ->get();

        foreach ($default_permissions_id as $key => $value) {
            if($value->id == $request->permission_id)
                return response("This is default permission for this project, you can't remove it", 400);
        }

        $users_with_permission = Projects_users::where('project_id', '=', $request->project_id)
        ->where('permission_id', '=', $request->permission_id)
        ->get();

        // If there is no user with this permission
        if($users_with_permission->isEmpty())
        {
                $check_permissions = $this->checkUserPermissionByUserID($user_id);
                $permissions = $check_permissions[0]->updatePermissions;
                
                if(!$permissions)
                {
                    return response("You don't have permissions to do that.", 400);
                }
                try
                {
                    $permission = Permission::where('project_id', '=', $request->project_id)
                                        ->where('id', '=', $request->permission_id)
                                        ->first();
                    $permission->delete();
                    return response("Permission has been deleted!", 200);
                } catch (\Throwable $th) {
                    throw $th;
                    error_log($th);
                    return response("Something went wrong!", 400);
                }
        }
        else {
            return response("You can't remove permission while users still use it!", 400);
        }
    }

    public function updatePermissionSettings(Request $request)
    {
        // If user is capable of changing this project 
        $user_id = $request->user()->id;

        $check_permissions = $this->checkUserPermissionByUserID($user_id, $request->project_id);
        $permission = $check_permissions[0]->updatePermissions;

        if(!$permission)
        {
            return response("You don't have permissions to do that.", 400);
        }
        
        $request->validate([
            'permission_id' => 'required|numeric',
            'name' => 'required|string',
            'read' => 'required|numeric|between:0,1',
            'write' => 'required|numeric|between:0,1',
            'update_name' => 'required|numeric|between:0,1',
            'add_users' => 'required|numeric|between:0,1',
            'remove_users' => 'required|numeric|between:0,1',
            'update_status' => 'required|numeric|between:0,1',
            'update_permission' => 'required|numeric|between:0,1',
        ]);

        DB::beginTransaction();
        try {
            $query = DB::table('permissions')
                ->where('id', '=', $request->permission_id)
                ->update([
                    'name' => $request->name,
                    'read' => $request->read,
                    'write' => $request->write,
                    'updateName' => $request->update_name,
                    'addUsers' => $request->add_users,
                    'removeUsers' => $request->remove_users,
                    'updateStatus' => $request->update_status,
                    'updatePermissions' => $request->update_permission,
                ]);
                DB::commit();
        } catch (\Throwable $th) {
            throw $th;
            error_log($th);
        }
    }

    function updateUserPermissions(Request $request)
    {
        $user_id = $request->user()->id;
        
        $check_permissions = $this->checkUserPermissionByUserID($user_id, $request->project_id);
        $permissions = $check_permissions[0]->updatePermissions;
        // Checks if there's record with permissions
        if(!$permissions)
        {
            return response("You don't have permissions to do that.", 400);
        }

        DB::beginTransaction();
        try {
            DB::table('projects_users')
                ->join('users', 'projects_users.user_id', '=', 'users.id')
                ->where('users.username', '=', $request->user)
                ->where('projects_users.project_id', '=', $request->project_id)
                ->update(['projects_users.permission_id' => $request->permission_id]);
            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
            error_log($th);
        }
    }

    public function deleteUserFromProject(Request $request)
    {
        $user_id = $request->user()->id;

        $check_permissions = $this->checkUserPermissionByUserID($user_id, $request->project_id);
        $permissions = $check_permissions[0]->removeUsers;

        if(!$permissions)
        {
            return response("You don't have permissions for this action.", 400);
        }
        
        $admins_except_deleted_user = DB::table('projects_users')
                                ->join('users', 'projects_users.user_id', '=', 'users.id')
                                ->join('permissions', 'projects_users.permission_id', '=', 'permissions.id')
                                ->where('projects_users.project_id', '=', $request->project_id)
                                ->where('users.username', 'IS NOT', $request->username)
                                ->where('permissions.name', '=', 'admin')
                                ->get();
        
        if(count($admins_except_deleted_user))
        {
            $user = DB::table('projects_users')
                        ->join('users', 'projects_users.user_id', '=', 'users.id')
                        ->where('users.username', '=', $request->username)
                        ->where('projects_users.project_id', '=', $request->project_id)
                        ->delete();
            return response('USER HAS BEEN REMOVED.', 200);
        }
        else {
            return response('User has not been removed.', 400);
        }
    }

    private function checkUserPermissionByUsername(string $username, int $project_id)
    {
        $validation = DB::table('permissions')
                        ->join('projects_users', 'permissions.id', '=', 'projects_users.permission_id')
                        ->join('users', 'projects_users.user_id', '=', 'users.id')
                        ->where('users.username', '=', $username)
                        ->where('projects_users.project_id', '=', $project_id)
                        ->get();

        return $validation;
    }

    private function checkUserPermissionByUserID(int $user_id, int $project_id)
    {
        $validation = DB::table('permissions')
                        ->join('projects_users', 'permissions.id', '=', 'projects_users.permission_id')
                        ->where('projects_users.user_id', '=', $user_id)
                        ->where('projects_users.project_id', '=', $project_id)
                        ->get();

        return $validation;
    }

    public function getCurrentUserPermissionInProject(Request $request)
    {
        
    }
}
