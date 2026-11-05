<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\JobRequest;
use App\Models\MaterialProduct;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NoticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function index()
    {
        return view('messages.index', ['threads' => Conversation::forUser(auth()->id())->with('initiator', 'recipient')->withCount('messages')->latest('updated_at')->paginate(20)]);
    }

    public function start(Request $r, string $type, int $id)
    {
        abort_unless(in_array($type, ['job', 'product']), 404);
        $model = ($type === 'job' ? JobRequest::query() : MaterialProduct::query())->findOrFail($id);
        Gate::authorize('view', $model);
        abort_unless($model->status === 'published' && $model->user_id !== auth()->id() && $model->user->status === 'active', 403);
        $thread = Conversation::firstOrCreate(['initiator_id' => auth()->id(), 'context_type' => $type, 'context_id' => $id], ['recipient_id' => $model->user_id, 'title' => $model->title ?? $model->name]);

        return redirect()->route('messages.show', $thread);
    }

    public function show(Conversation $conversation)
    {
        abort_unless($conversation->contains(auth()->user()), 403);
        $conversation->load('initiator', 'recipient');
        $messages = $conversation->messages()->with('user')->latest('id')->paginate(50);
        $conversation->timestamps = false;
        $conversation->update([auth()->id() === $conversation->initiator_id ? 'initiator_read_at' : 'recipient_read_at' => now()]);

        return view('messages.show', compact('conversation', 'messages'));
    }

    public function send(Request $r, Conversation $conversation)
    {
        abort_unless($conversation->contains($r->user()), 403);
        $d = $r->validate(['body' => 'required|string|min:1|max:4000'], ['body.required' => 'متن پیام را وارد کنید.']);
        DB::transaction(function () use ($r, $conversation, $d) {
            $other = User::findOrFail($conversation->initiator_id === $r->user()->id ? $conversation->recipient_id : $conversation->initiator_id);
            abort_unless($other->status === 'active', 403);
            $conversation->messages()->create(['user_id' => $r->user()->id, 'body' => $d['body']]);
            $conversation->touch();
            app(NoticeService::class)->send($other, 'پیام جدید', $conversation->title, '/messages/'.$conversation->id);
            app(AuditService::class)->record('message.sent', $conversation);
        });

        return back()->with('success', 'پیام ارسال شد.');
    }
}
