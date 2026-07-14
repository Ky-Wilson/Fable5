<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Player;
use App\Models\Room;
use App\Models\Round;
use App\Services\OpenRouter;
use App\Support\Questions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function __construct(private readonly OpenRouter $ai) {}

    /** Crée un salon ; le créateur est le joueur n°1. */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'   => 'required|string|max:30',
            'gender' => 'required|in:m,f',
        ]);

        $room = Room::create(['code' => Room::generateCode()]);
        $token = Str::random(40);
        $room->players()->create([
            'num'    => 1,
            'name'   => trim($data['name']),
            'gender' => $data['gender'],
            'token'  => $token,
        ]);

        return response()->json(['ok' => true, 'code' => $room->code, 'token' => $token]);
    }

    /** Rejoint un salon existant en tant que joueur n°2. */
    public function join(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'   => 'required|string|max:30',
            'code'   => 'required|string|max:10',
            'gender' => 'required|in:m,f',
        ]);

        $room = Room::where('code', strtoupper(trim($data['code'])))->first();
        if (! $room) {
            return $this->fail('room_not_found', 404);
        }
        if ($room->players()->count() >= 2) {
            return $this->fail('room_full');
        }

        $token = Str::random(40);
        $room->players()->create([
            'num'    => 2,
            'name'   => trim($data['name']),
            'gender' => $data['gender'],
            'token'  => $token,
        ]);

        return response()->json(['ok' => true, 'code' => $room->code, 'token' => $token]);
    }

    /** État complet du salon, filtré selon le joueur qui demande. */
    public function state(Request $request): JsonResponse
    {
        [$room, $me, $partner] = $this->auth($request);

        $out = [
            'ok' => true,
            'room' => [
                'code'         => $room->code,
                'state'        => $room->state,
                'mode'         => $room->mode,
                'pack'         => $room->pack,
                'ai'           => $room->ai,
                'total_rounds' => $room->total_rounds,
            ],
            'me'      => ['num' => $me->num, 'name' => $me->name, 'gender' => $me->gender, 'score' => $me->score],
            'partner' => $partner ? ['name' => $partner->name, 'gender' => $partner->gender, 'score' => $partner->score] : null,
            'ai_available' => $this->ai->enabled(),
            'packs' => Questions::packList(),
            'discover_packs' => Questions::discoverPackList(),
        ];

        $round = $room->currentRound();
        if ($round && $room->state === 'playing' && $room->mode === 'discover') {
            $mine = $me->num === 1 ? $round->target_answer : $round->guess_answer;
            $theirs = $me->num === 1 ? $round->guess_answer : $round->target_answer;
            $out['round'] = [
                'num'             => $round->num,
                'status'          => $round->status,
                // Les accords suivent le sexe du lecteur.
                'question'        => Questions::render($round->question, '', $me->gender),
                'my_submitted'    => $mine !== null,
                'other_submitted' => $theirs !== null,
            ];
            if ($round->status !== 'answering') {
                $out['round'] += ['my_answer' => $mine, 'partner_answer' => $theirs];
            }
        } elseif ($round && $room->state === 'playing') {
            $iAmTarget = $me->num === $round->target_num;
            $target = $iAmTarget ? $me : $partner;
            $guesser = $iAmTarget ? $partner : $me;
            $targetName = $target?->name ?? '?';
            $mine = $iAmTarget ? $round->target_answer : $round->guess_answer;
            $theirs = $iAmTarget ? $round->guess_answer : $round->target_answer;

            $out['round'] = [
                'num'             => $round->num,
                'status'          => $round->status,
                'question'        => Questions::render($round->question, $targetName, $target?->gender ?? 'm', $guesser?->gender ?? 'm'),
                'i_am_target'     => $iAmTarget,
                'target_name'     => $targetName,
                'my_submitted'    => $mine !== null,
                'other_submitted' => $theirs !== null,
            ];
            // Verdict de la manche précédente (feedback après enchaînement).
            if ($round->num > 1) {
                $prev = $room->rounds()->where('num', $round->num - 1)->first();
                $out['round']['prev_correct'] = (bool) $prev?->correct;
            }
            // Les réponses ne sont dévoilées que quand les deux ont répondu.
            if ($round->status !== 'answering') {
                $out['round'] += [
                    'target_answer' => $round->target_answer,
                    'guess_answer'  => $round->guess_answer,
                    'correct'       => $round->correct,
                ];
            }
        }

        if ($room->state === 'finished' && $room->mode === 'discover') {
            $out['recap'] = $room->rounds()->orderBy('num')->get()->map(fn (Round $r) => [
                'num'            => $r->num,
                'question'       => Questions::render($r->question, '', $me->gender),
                'my_answer'      => $me->num === 1 ? $r->target_answer : $r->guess_answer,
                'partner_answer' => $me->num === 1 ? $r->guess_answer : $r->target_answer,
            ])->all();
        } elseif ($room->state === 'finished') {
            $out['recap'] = $room->rounds()->orderBy('num')->get()->map(function (Round $r) use ($me, $partner) {
                $target = $me->num === $r->target_num ? $me : $partner;
                $guesser = $me->num === $r->target_num ? $partner : $me;

                return [
                'num'           => $r->num,
                'question'      => Questions::render($r->question, $target?->name ?? '?', $target?->gender ?? 'm', $guesser?->gender ?? 'm'),
                'target_name'   => $target?->name ?? '?',
                'target_answer' => $r->target_answer,
                'guess_answer'  => $r->guess_answer,
                'correct'       => (bool) $r->correct,
                ];
            })->all();
        }

        // Question du jour.
        $day = now()->toDateString();
        $today = $room->dailyEntries()->whereDate('day', $day)->first();
        $myCol = $me->num === 1 ? 'answer1' : 'answer2';
        $otherCol = $me->num === 1 ? 'answer2' : 'answer1';
        $both = $today && $today->answer1 !== null && $today->answer2 !== null;

        $out['daily'] = [
            // Les accords de la question du jour suivent le sexe du lecteur.
            'question'         => Questions::render($today->question ?? Questions::dailyFor($room, $day), '', $me->gender),
            'my_answer'        => $today?->{$myCol},
            'partner_answered' => $today?->{$otherCol} !== null,
            'partner_answer'   => $both ? $today->{$otherCol} : null,
        ];

        $out['daily_history'] = $room->dailyEntries()
            ->whereDate('day', '<', $day)
            ->whereNotNull('answer1')
            ->whereNotNull('answer2')
            ->orderByDesc('day')
            ->limit(60)
            ->get()
            ->map(fn (DailyEntry $e) => [
                'day'            => $e->day->format('Y-m-d'),
                'question'       => Questions::render($e->question, '', $me->gender),
                'my_answer'      => $e->{$myCol},
                'partner_answer' => $e->{$otherCol},
            ])->all();

        return response()->json($out);
    }

    /** Lance une partie : choix du pack, du nombre de manches, IA en option. */
    public function start(Request $request): JsonResponse
    {
        [$room, , $partner] = $this->auth($request);
        if (! $partner) {
            return $this->fail('partner_missing');
        }
        if ($room->state === 'playing') {
            return response()->json(['ok' => true]); // L'autre a déjà lancé.
        }

        $mode = $request->input('mode') === 'discover' ? 'discover' : 'guess';
        $bank = $mode === 'discover' ? Questions::DISCOVER_PACKS : Questions::PACKS;
        $pack = (string) $request->input('pack', '');
        if (! isset($bank[$pack])) {
            $pack = array_key_first($bank);
        }
        $total = min(20, max(4, (int) $request->input('rounds', 10)));

        $questions = null;
        $aiUsed = false;
        if ($request->boolean('ai')) {
            $questions = $this->ai->generateQuestions($pack, $total, $mode);
            $aiUsed = $questions !== null;
        }
        $questions ??= Questions::pick($pack, $total, $mode);

        DB::transaction(function () use ($room, $mode, $pack, $aiUsed, $questions) {
            $room->rounds()->delete();
            $room->players()->update(['score' => 0]);
            $room->update([
                'state'        => 'playing',
                'mode'         => $mode,
                'pack'         => $pack,
                'ai'           => $aiUsed,
                'total_rounds' => count($questions),
                'questions'    => $questions,
            ]);
            $room->rounds()->create([
                'num'        => 1,
                'question'   => $questions[0],
                // En mode découverte il n'y a pas de joueur « cible ».
                'target_num' => $mode === 'discover' ? 0 : random_int(1, 2),
            ]);
        });

        return response()->json(['ok' => true, 'ai_used' => $aiUsed]);
    }

    /** Enregistre une réponse (vraie réponse ou devinette selon le joueur). */
    public function answer(Request $request): JsonResponse
    {
        [$room, $me] = $this->auth($request);
        $data = $request->validate(['text' => 'required|string|max:500']);

        $round = $room->currentRound();
        if (! $round || $room->state !== 'playing' || $round->status !== 'answering') {
            return $this->fail('not_answering');
        }

        // En mode découverte, chacun répond pour soi : joueur 1 → target_answer,
        // joueur 2 → guess_answer. En mode devinettes, selon le rôle.
        $col = $room->mode === 'discover'
            ? ($me->num === 1 ? 'target_answer' : 'guess_answer')
            : ($me->num === $round->target_num ? 'target_answer' : 'guess_answer');
        if ($round->{$col} !== null) {
            return response()->json(['ok' => true]); // Double envoi : sans effet.
        }

        DB::transaction(function () use ($round, $col, $data) {
            $round->update([$col => trim($data['text'])]);
            $round->refresh();
            if ($round->target_answer !== null && $round->guess_answer !== null) {
                $round->update(['status' => 'reveal']);
            }
        });

        return response()->json(['ok' => true]);
    }

    /** Le joueur concerné juge si la devinette est bonne, puis manche suivante. */
    public function validateGuess(Request $request): JsonResponse
    {
        [$room, $me, $partner] = $this->auth($request);
        if ($room->mode !== 'guess') {
            return $this->fail('wrong_mode');
        }

        $round = $room->currentRound();
        if (! $round || $round->status !== 'reveal') {
            return $this->fail('not_in_reveal');
        }
        if ($me->num !== $round->target_num) {
            return $this->fail('only_target_validates', 403);
        }

        $correct = $request->boolean('correct');

        DB::transaction(function () use ($room, $round, $partner, $correct) {
            $round->update(['correct' => $correct, 'status' => 'done']);
            if ($correct && $partner) {
                $partner->increment('score');
            }

            if ($round->num >= $room->total_rounds) {
                $room->update(['state' => 'finished']);

                return;
            }
            $questions = $room->questions ?? [];
            $room->rounds()->create([
                'num'        => $round->num + 1,
                'question'   => $questions[$round->num] ?? $questions[array_rand($questions)],
                'target_num' => $round->target_num === 1 ? 2 : 1,
            ]);
        });

        return response()->json(['ok' => true]);
    }

    /** Mode découverte : passe à la question suivante après la révélation. */
    public function next(Request $request): JsonResponse
    {
        [$room] = $this->auth($request);
        if ($room->mode !== 'discover') {
            return $this->fail('wrong_mode');
        }

        $round = $room->currentRound();
        if (! $round || $round->status !== 'reveal') {
            return response()->json(['ok' => true]); // Déjà avancé par l'autre.
        }

        DB::transaction(function () use ($room, $round) {
            $round->update(['status' => 'done']);

            if ($round->num >= $room->total_rounds) {
                $room->update(['state' => 'finished']);

                return;
            }
            $questions = $room->questions ?? [];
            $room->rounds()->create([
                'num'        => $round->num + 1,
                'question'   => $questions[$round->num] ?? $questions[array_rand($questions)],
                'target_num' => 0,
            ]);
        });

        return response()->json(['ok' => true]);
    }

    /** Retour au salon pour relancer une partie. */
    public function replay(Request $request): JsonResponse
    {
        [$room] = $this->auth($request);

        DB::transaction(function () use ($room) {
            $room->rounds()->delete();
            $room->players()->update(['score' => 0]);
            $room->update(['state' => 'lobby', 'questions' => null]);
        });

        return response()->json(['ok' => true]);
    }

    /** Réponse à la question du jour (non modifiable une fois envoyée). */
    public function dailyAnswer(Request $request): JsonResponse
    {
        [$room, $me] = $this->auth($request);
        $data = $request->validate(['text' => 'required|string|max:1000']);

        $day = now()->toDateString();
        $col = $me->num === 1 ? 'answer1' : 'answer2';

        DB::transaction(function () use ($room, $day, $col, $data) {
            $entry = $room->dailyEntries()->whereDate('day', $day)->lockForUpdate()->first()
                ?? $room->dailyEntries()->create([
                    'day'      => $day,
                    'question' => Questions::dailyFor($room, $day),
                ]);
            if ($entry->{$col} === null) {
                $entry->update([$col => trim($data['text'])]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /** @return array{0: Room, 1: Player, 2: ?Player} */
    private function auth(Request $request): array
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $token = (string) $request->input('token');

        $room = Room::where('code', $code)->first();
        abort_if(! $room, 404, 'room_not_found');

        $me = null;
        $partner = null;
        foreach ($room->players as $player) {
            if (hash_equals($player->token, $token)) {
                $me = $player;
            } else {
                $partner = $player;
            }
        }
        abort_if(! $me, 403, 'auth_failed');

        return [$room, $me, $partner];
    }

    private function fail(string $error, int $status = 400): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $error], $status);
    }
}
