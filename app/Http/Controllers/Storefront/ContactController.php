<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Shared\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SendContactMessageRequest;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function index(): Response
    {
        return Inertia::render('storefront/contact');
    }

    /**
     * Le message est à la fois persisté (`contact_messages`, consultable dans
     * l'admin — voir Admin\ContactMessageController) et transmis par email aux
     * admins avec reply-to sur le client (ContactMessageReceived), pour pouvoir
     * répondre par mail sans repasser par l'admin.
     */
    public function store(SendContactMessageRequest $request): RedirectResponse
    {
        $message = ContactMessage::query()->create($request->validated());

        Notification::send(
            $this->users->admins(),
            new ContactMessageReceived($message),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Message envoyé ! Nous te répondrons rapidement.'),
        ]);

        return back();
    }
}
