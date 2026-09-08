<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CardController extends Controller
{
    public function index(): View
    {
        $cards = Card::orderBy('position')->get();

        $states = CardState::where('user_id', Auth::id())
            ->pluck('status', 'card_id');

        return view('cards', [
            'deck' => $cards->map(fn (Card $card) => [
                'id' => $card->id,
                'sec' => $card->section,
                'de' => $card->de,
                'fr' => $card->fr,
                'ex' => $card->example,
            ])->all(),
            'states' => $states->all(),
        ]);
    }

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
     * Drop every mark for the signed-in user.
     */
    public function reset(): JsonResponse
    {
        CardState::where('user_id', Auth::id())->delete();

        return response()->json(['ok' => true]);
    }
}
