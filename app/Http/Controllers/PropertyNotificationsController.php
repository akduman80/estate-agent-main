<?php

namespace App\Http\Controllers;

use App\Models\PropertyComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyNotificationsController extends Controller
{
    public function index()
    {
        $notices = DB::table('property_notifications')->where('user_id', auth()->id())->orderByDesc('id')->paginate(20);
        $comments = PropertyComment::with(['listing', 'post'])->whereIn('id', $notices->pluck('comment_id'))->get()->keyBy('id');

        return view('account.property-notifications', ['notices' => $notices, 'comments' => $comments, 'enabled' => DB::table('property_interaction_preferences')->where('user_id', auth()->id())->value('notifications') ?? true]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['enabled' => ['sometimes', 'boolean']]);
        DB::table('property_interaction_preferences')->updateOrInsert(['user_id' => auth()->id()], ['notifications' => (bool) ($data['enabled'] ?? false), 'updated_at' => now()]);

        return back()->with('status', 'Bildirim tercihiniz kaydedildi.');
    }

    public function read(int $id)
    {
        DB::table('property_notifications')->where('user_id', auth()->id())->where('id', $id)->update(['read_at' => now()]);

        return back();
    }
}
