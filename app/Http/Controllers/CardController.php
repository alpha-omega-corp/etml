<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * What the signed-in user knows. The two endpoints below are the whole
 * persistence side of the card game, whatever language it is playing.
 */
class CardController extends Controller
{
    /**
     * Mark a card as known / to review, or clear it (status = null).
     */
    public function setStatus(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'status' => ['present', 'nullable', 'in:'.CardState::STATUS_KNOWN.','.CardState::STATUS_REVIEW],
        ]);

        $userId = Auth::id();

        if ($data['status'] === null) {
            CardState::where('user_id', $userId)->where('card_id', $card->id)->delete();
        } else {
            CardState::updateOrCreate(
                ['user_id' => $userId, 'card_id' => $card->id],
                ['status' => $data['status']],
            );
        }

        return response()->json(['id' => $card->id, 'status' => $data['status']]);
    }

    /**
     * Drop every mark for the signed-in user, in every language.
     */
    public function reset(): JsonResponse
    {
        CardState::where('user_id', Auth::id())->delete();

        return response()->json(['ok' => true]);
    }
}
