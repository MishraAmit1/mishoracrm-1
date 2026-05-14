<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'                  => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string', 'max:5000'],
            'status'                 => ['required', 'in:pending,in_progress,completed,cancelled'],
            'priority'               => ['required', 'in:low,medium,high'],
            'taskable_type'          => ['nullable', 'in:App\Models\Lead,App\Models\Contact,App\Models\Deal'],
            'taskable_id'            => ['nullable', 'integer'],
            'assigned_to'            => ['nullable', 'exists:users,id'],
            'due_at'                 => ['nullable', 'date'],
            'completed_at'           => ['nullable', 'date'],
            'contact_id'             => ['nullable', 'exists:contacts,id'],
            'lead_id'                => ['nullable', 'exists:leads,id'],
            'deal_id'                => ['nullable', 'exists:deals,id'],
            'due_date'               => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required.',
            'status.required' => 'Task status is required.',
            'status.in'       => 'Invalid status selected.',
            'priority.required' => 'Task priority is required.',
            'priority.in'       => 'Invalid priority selected.',
            'taskable_type.required' => 'Taskable type is required.',
            'taskable_type.in'       => 'Invalid taskable type selected.',
            'taskable_id.required'   => 'Taskable ID is required.',
            'assigned_to.exists'    => 'Assigned user does not exist.',
        ];
    }
}
