@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Edit Lead</div>

                    <div class="card-body">
                        <form method="PUT" action="{{ route('tenant.leads.edit', $lead->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ $lead->name }}" required>
                            </div
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ $lead->email }}" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ $lead->phone }}" required>    
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="new" {{ $lead->status == 'new' ? 'selected' : '' }}>New</option>
                                    <option value="contacted" {{ $lead->status == 'contacted' ? 'selected' : '' }}>Contacted</option>
                                    <option value="qualified" {{ $lead->status == 'qualified' ? 'selected' : '' }}>Qualified</option>
                                    <option value="lost" {{ $lead->status == 'lost' ? 'selected' : '' }}>Lost</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Lead</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 
