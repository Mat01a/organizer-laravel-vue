<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $user = $request->user();
        $project_id = $request->id;

        $permissions = $this->checkUserPermissionByUserID($user->id, $project_id);

        if(!count($permissions))
        {
            return response("You don't have permissions for this action.", 400);
        }

        $permissionsRead = $permissions[0]->read;

        if($permissionsRead == 0)
        {
            return response("You don't have permissions for this action.", 400);
        }
        
        $tasks = DB::table('tasks')
                    ->join('users', 'tasks.created_by_user_id', '=', 'users.id')
                    ->where('project_id', '=', $project_id)
                    ->select('tasks.*', 'users.username', DB::raw("strftime('%Y-%m-%d', tasks.created_at) as created"))
                    ->get();

        if(!count($tasks))
        {
            return response([], 200);
        }

        return response($tasks, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $permissions = $this->checkUserPermissionByUserID($request->user()->id, $request->project_id);

        if(!count($permissions))
        {
            return response("You don't have permissions for this action.", 400);
        }

        if($permissions[0]->write == 0)
        {
            return response("You don't have permissions for this action.", 400);
        }

        $request->validate([
            'project_id' => 'required|integer',
            'name' => 'required|string',
            'description' => 'required|string',
        ]);

        try {
            DB::beginTransaction();
            $task = new Task;
            $task->project_id = $request->project_id;
            $task->name = $request->name;
            $task->description = $request->description;
            $task->created_by_user_id = $request->user()->id;
            $task->status = 0;
            $task->save();
            DB::commit();
            return response('You have created task successfully', 200);
        } catch (\Throwable $th) {
            throw $th;
            error_log($th);
            return response('Something went wrong', 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        //
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        //
        $permissions = $this->checkUserPermissionByUserID($request->user()->id, $request->project_id);

        if(!count($permissions))
        {
            return response("You don't have permissions for this action.", 400);
        }
        
        if($permissions[0]->write == 0)
        {
            return response("You don't have permissions for this action.", 400);
        }

        $task = DB::table('tasks')
                    ->where('id', '=', $request->id)
                    ->where('project_id', '=', $request->project_id)
                    ->get();
        $new_status = ($task[0]->status == 1) ? 0 : 1;
        $updated = DB::table('tasks')
                    ->where('id', '=', $request->id)
                    ->where('project_id', '=', $request->project_id)
                    ->update(['status' => $new_status]);
        return response('TRUE', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $task = Task::find($id);
        $project_id = $task->project_id;

        $permissions = $this->checkUserPermissionByUserID($request->user()->id, $project_id);

        if(!count($permissions))
        {
            return response("You don't have permissions for this action.", 400);
        }

        if($permissions[0]->removeUsers == 0)
        {
            return response("You don't have permissions for this action.", 400);
        }
        $task->delete();
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
}
