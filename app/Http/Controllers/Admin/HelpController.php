<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function questionBulkUploadInfo()
    {
        return view('admin.help.question_bulk_upload');
    }

    // Add other help/instruction methods here
}
