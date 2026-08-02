<?php

namespace App\Models;

use Database\Factories\ContactMessageReplyLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Historique des reponses envoyees depuis l'admin pour un ContactMessage
 * (26.5) - la reponse du client, elle, arrive par email reel (Reply-To sur
 * l'admin, voir ContactMessageController::reply) et n'est pas re-ingeree
 * dans l'app (pas de webhook de reception configure).
 *
 * @property int $id
 * @property int $contact_message_id
 * @property int|null $user_id
 * @property string $message
 * @property Carbon $created_at
 */
#[Fillable(['contact_message_id', 'user_id', 'message'])]
class ContactMessageReplyLog extends Model
{
    /** @use HasFactory<ContactMessageReplyLogFactory> */
    use HasFactory;

    protected $table = 'contact_message_replies';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContactMessage, $this>
     */
    public function contactMessage(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
