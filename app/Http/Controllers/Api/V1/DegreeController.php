<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Degree;
class DegreeController extends Controller
{
    public function index()
    {
        return Degre::get();
    }
}