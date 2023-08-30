<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Project;

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
            $last_project_id = Project::select('id')->get()->last()->id;


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

    public function project_difference($column, $data, $operator = '=')
    {
        $difference = Project::where($column, $operator, $data)->get();

        // If there's difference returns 1, or if there is not, returns 0.
        return count($difference);
    }


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
        if($permissions_project && !$this->project_difference('name', $changed_name))
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

}
