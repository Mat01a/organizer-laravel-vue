<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Project;
use App\Models\User;

class ProjectController extends Controller
{
    //Get all projects for requested user
    public function index(Request $request)
    {
        $user_request_id = $request->user()->id;
        $projects_id = Permission::select('project_id')->where('user_id', '=', $user_request_id)->get();
        
        //Get all projects
        $projects = [];
        foreach($projects_id as $id)
        {
            $current_id = $id->project_id;
            array_push($projects, Project::where('id', '=', $current_id)->get());
        }
        return response($projects, 200);
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
    
            $permissions = new Permission;
            
            //creating admin permission for project creator
            $permissions->user_id = $user_request_id;
            $permissions->project_id = $last_project_id+1;
            $permissions->name = 'admin';
            $permissions->read = 1;
            $permissions->write = 1;
            $permissions->givePermissions = 1;
            $permissions->save();
            DB::commit();
        } catch (\Throwable $th) {
            throw $th;
            DB::rollBack();
        }

        //return response(200, "New project has been created");
    }

    public function projectDifference($column, $data, $operator = '=')
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

        $query = Permission::where([
            ['user_id', $user->id],
            ['project_id', $project_changes->id]
            ])->get();


        // Checking if user have permissions for changing name
        $permissions_project = $query[0]->givePermissions;


        // Changes name if there's difference between current one
        if($permissions_project && !$this->projectDifference('name', $changed_name))
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
        try {

            $requested_user = User::where('username', '=', $request->username)->first();
            if($requested_user)
            {
                $requested_user_permissions = Permission::where('user_id', '=', $requested_user->id)->get();
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
                $permissions = new Permission;

                //giving regular permissions
                $permissions->user_id = $user_to_be_added[0]->id;
                $permissions->project_id = $request->id;
                $permissions->name = 'regular';
                $permissions->read = 0;
                $permissions->write = 0;
                $permissions->givePermissions = 0;
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
    

    public function getUsersInProject($id)
    {
        try {
            $permissions_users = Permission::select("user_id", "name")
                ->where('project_id', '=', $id)
                ->paginate(4);
            $users = [];
            
            foreach ($permissions_users as $key => $value) {
                $users[$key] = User::select("name", "username")
                    ->where('id', '=', $value->user_id)
                    ->get()[0];
            }
           return response([$users, $permissions_users], 200);

        } catch (\Throwable $th) {
            throw $th;
            return response($th);
        }
    }
}
