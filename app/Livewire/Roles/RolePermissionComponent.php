<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RolePermissionComponent extends Component
{
    use WithPagination;

    public $roleId;
    public $name;
    public $selectedPermissions = [];
    public $search = '';
    public $modalFormVisible = false;
    public $confirmingDelete = false;
    public $deleteId;

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'name' => 'required|string|min:3|max:255|unique:roles,name',
        'selectedPermissions' => 'array',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateModal()
    {
        $this->reset(['name', 'selectedPermissions', 'roleId']);
        $this->dispatch('openModal', name: 'roleModal');

    }

    public function showEditModal($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->resetValidation();
        $this->dispatch('openModal', name: 'roleModal');

        $this->modalFormVisible = true;
    }

    public function save()
    {
        $validated = $this->validate(
            $this->roleId
                ? [
                    'name' => ['required', 'string', 'min:3', 'max:255', Rule::unique('roles')->ignore($this->roleId)],
                    'selectedPermissions' => 'array'
                ]
                : $this->rules
        );

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            $role->update(['name' => $this->name]);
        } else {
            $role = Role::create(['name' => $this->name]);
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->modalFormVisible = false;
        $this->reset(['name', 'selectedPermissions', 'roleId']);
        $this->dispatch('closeModal', name: 'roleModal');

        notyf()->success('Rôle enregistré avec succès.');

    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('openModal', name: 'deleteModal');

    }

    public function delete()
    {
        Role::findOrFail($this->deleteId)->delete();
        $this->confirmingDelete = false;
        $this->dispatch('closeModal', name: 'deleteModal');
        notyf()->success('Rôle supprimé avec succès.');

    }

    public function render()
    {
        $roles = Role::where('name', 'like', "%{$this->search}%")
            ->orderBy('name')
            ->paginate(10);

        $permissions = Permission::orderBy('name')->get();

        return view('livewire.roles.role-permission-component', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}

