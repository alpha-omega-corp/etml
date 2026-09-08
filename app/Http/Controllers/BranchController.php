<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Switch subject. The chapter choice is dropped with it, so the sub-nav
     * always opens on the new branch's own first chapter.
     */
    public function show(Request $request, Branch $branch): RedirectResponse
    {
        $request->session()->put('branch_id', $branch->id);
        $request->session()->forget('chapter_id');

        return redirect()->route('cards');
    }
}
