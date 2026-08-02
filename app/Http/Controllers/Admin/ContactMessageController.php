<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyToContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\ContactMessageReplyLog;
use App\Notifications\ContactMessageReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(): Response
    {
        $messages = ContactMessage::query()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $messages->through(fn (ContactMessage $message) => [
            'id' => $message->id,
            'name' => $message->name,
            'email' => $message->email,
            'subject' => $message->subject,
            'isRead' => $message->read_at !== null,
            'isReplied' => $message->replied_at !== null,
            'createdAt' => $message->created_at->toIso8601String(),
        ]);

        return Inertia::render('admin/contact-messages/index', [
            'messages' => $messages,
            'unreadCount' => ContactMessage::query()->unread()->count(),
        ]);
    }

    /**
     * Marque le message comme lu à la première consultation — pas de bouton
     * dédié, la lecture elle-même fait foi (même logique qu'une boîte mail).
     */
    public function show(ContactMessage $contactMessage): Response
    {
        if ($contactMessage->read_at === null) {
            $contactMessage->update(['read_at' => now()]);
        }

        $contactMessage->load(['replies' => fn ($query) => $query->with('user:id,name')->oldest('id')]);

        return Inertia::render('admin/contact-messages/show', [
            'message' => [
                'id' => $contactMessage->id,
                'name' => $contactMessage->name,
                'email' => $contactMessage->email,
                'subject' => $contactMessage->subject,
                'message' => $contactMessage->message,
                'readAt' => $contactMessage->read_at?->toIso8601String(),
                'repliedAt' => $contactMessage->replied_at?->toIso8601String(),
                'createdAt' => $contactMessage->created_at->toIso8601String(),
            ],
            'replies' => $contactMessage->replies->map(function (ContactMessageReplyLog $reply) {
                $author = $reply->user;

                return [
                    'id' => $reply->id,
                    'message' => $reply->message,
                    'authorName' => $author !== null ? $author->name : 'Admin',
                    'createdAt' => $reply->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Repond directement depuis l'admin (pas de simple `mailto:`) : le client
     * recoit un vrai email (ContactMessageReply, via Notification::route car
     * ce n'est pas forcement un User), sans que l'admin ait a ouvrir son
     * propre client mail. Reply-To pose sur l'email de l'admin qui repond
     * (pas `noreply@`) : si le client repond a son tour, ca doit atterrir
     * dans une vraie boite consultee, pas dans le vide.
     *
     * La reponse envoyee est aussi journalisee (ContactMessageReplyLog) pour
     * garder un fil des echanges cote admin — la reponse du client, elle,
     * arrive par email reel et n'est pas re-ingeree dans l'app (pas de
     * webhook de reception configure, decision du 2026-08-02).
     */
    public function reply(ReplyToContactMessageRequest $request, ContactMessage $contactMessage): RedirectResponse
    {
        $replyText = $request->validated('reply');

        Notification::route('mail', [$contactMessage->email => $contactMessage->name])
            ->notify(new ContactMessageReply($contactMessage, $replyText, $request->user()));

        $contactMessage->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $replyText,
        ]);

        $contactMessage->update(['replied_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Réponse envoyée à '.$contactMessage->email.'.']);

        return back();
    }
}
