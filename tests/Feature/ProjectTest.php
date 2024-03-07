<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class ProjectTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_showing_all_projects(): void
    {

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/api/projects');
        
        $response->assertStatus(200);
    }

    public function test_store_new_project(): void
    {
        $user = User::factory()->create();
        
        $project = $this->actingAs($user)->post('/api/projects', ['name' => 'Test Project']);
        $project->assertStatus(200);
    }

    public function test_update_project(): void
    {

        $user = User::factory()->create();
        $project = $this->actingAs($user)->post('/api/projects', ['name' => 'Test Project']);
        $user_project = $this->actingAs($user)->get('/api/projects');

        $res = $user_project->getContent();
        $json = json_decode($res);

        $project_update = $this->actingAs($user)->patch('/api/projects/'.$json->data[0]->id, ['name' => 'New Test Project Name']);        
        $project_update->assertStatus(200);
    }

    public function test_finding_proposed_users(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();
        $user_to_find = User::find(1);

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];
        $proposed_users = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/users/'. $user_to_find->name);

        $proposed_users->assertStatus(200);
    }

    public function test_adding_user_to_project(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();
        $user_to_find = User::find(1);

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $adding_user = $this->actingAs($user)->post('/api/projects/add-user', ['username' =>$user_to_find->username, 'project_id' => $json_data->id]);

        $adding_user->assertStatus(200);
    }

    public function test_getting_users_in_project(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $getting_users = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/users');
        
        $getting_users->assertStatus(200);
    }

    public function test_changing_project_status(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $changing_status = $this->post('/api/projects/change-status', ['project_id' => $json_data->id, 'status' => ($json_data->status) ? ($json_data->status) : !($json_data->status)]);

        $changing_status->assertStatus(200);
    }

    public function test_leaving_project(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();
        $user_to_find = User::find(1);

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $leaving_project = $this->actingAs($user_to_find)->post('/api/projects/leave', ['id' => $json_data->id]);

        $leaving_project->assertStatus(200);
    }

    public function test_getting_permissions_in_project(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $permissions = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/permissions');
        $permissions->assertStatus(200);
    }

    public function test_adding_permissions_to_project(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $adding_permissions = $this->actingAs($user)->post('/api/projects/add-permission', ['project_id' => $json_data->id, 'name' => 'Test']);
        $adding_permissions->assertStatus(200);
    }

    public function test_remove_permission_from_project(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $test_permission_in_project = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/permissions');
        $json_permission_data = end(json_decode($test_permission_in_project->getContent())->data);

        $remove_permission = $this->actingAs($user)->patch('/api/projects/'.$json_data->id.'/remove-permission', ["project_id" => $json_data->id, "permission_id" => $json_permission_data->id]);

        $remove_permission->assertStatus(200);
    }

    public function test_updating_permission_settings(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $test_permission_in_project = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/permissions');
        $json_permission_data = end(json_decode($test_permission_in_project->getContent())->data);
        
        $updating_permission = $this->actingAs($user)->patch('/api/projects/'.$json_data->id.'/update-permission-settings', [
            'permission_id' => $json_permission_data->id,
            'name' => $json_permission_data->name,
            'read' => $json_permission_data->read,
            'write' => $json_permission_data->write,
            'update_name' => $json_permission_data->updateName,
            'add_users' => $json_permission_data->addUsers,
            'remove_users' => $json_permission_data->removeUsers,
            'update_status' => $json_permission_data->updateStatus,
            'update_permission' => $json_permission_data->updatePermissions
        ]);
        $updating_permission->assertStatus(200);
    }

    public function test_updating_user_permissions(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $test_permission_in_project = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/permissions');
        $json_permission_data = end(json_decode($test_permission_in_project->getContent())->data);        $user = User::query()->orderBy('id', 'desc')->first();

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $test_permission_in_project = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/permissions');
        $json_permission_data = end(json_decode($test_permission_in_project->getContent())->data);

        $updating_user_permissions = $this->actingAs($user)->patch('/api/projects/'.$json_data->id.'/update-user-permission', ['permission_id' => $json_permission_data->id]);
        $updating_user_permissions->assertStatus(200);
    }

    public function test_deleting_user_from_project(): void
    {
        $user = User::query()->orderBy('id', 'desc')->first();
        $user_to_find = User::find(1);

        $latest_project = $this->actingAs($user)->get('/api/projects');
        $json_data = json_decode($latest_project->getContent())->data[0];

        $test_permission_in_project = $this->actingAs($user)->get('/api/projects/'.$json_data->id.'/permissions');

        $remove_user_from_project = $this->actingAs($user)->post('/api/projects/delete-user', ['project_id' => $json_data->id, 'username' => $user_to_find->username]);
        
        $remove_user_from_project->assertStatus(200);
    }
}
