<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        // $tenants = Tenant::latest()->paginate(10);
        // return view('superadmin.tenants.index', compact('tenants'));
    }
}
