<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class AdminController extends Controller
{
    public function index(): Response
    {
        return response()->view('admin');
    }
}
