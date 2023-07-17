<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Project;

class ProjectController extends Controller
{
    //
    public function index(Request $request)
    {
        $user_request_id = $request->user()->id;
        $projects_id = Permission::select('project_id')->where('user_id', '=', $user_request_id)->get()->value('project_id');
        $projects = Project::where('id', '=', $projects_id)->get();
        return response($projects, 200);
    }
}
