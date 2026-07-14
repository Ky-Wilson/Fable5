<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{code: string, t1: string, t2: string} */
    private function createCouple(): array
    {
        $create = $this->postJson('/api/create', ['name' => 'Alice', 'gender' => 'f'])->assertOk()->json();
        $join = $this->postJson('/api/join', ['name' => 'Bob', 'code' => $create['code'], 'gender' => 'm'])
            ->assertOk()->json();

        return ['code' => $create['code'], 't1' => $create['token'], 't2' => $join['token']];
    }

    public function test_gender_is_required(): void
    {
        $this->postJson('/api/create', ['name' => 'Alice'])->assertStatus(422);
        $this->postJson('/api/create', ['name' => 'Alice', 'gender' => 'x'])->assertStatus(422);
    }

    public function test_create_and_join_room(): void
    {
        $c = $this->createCouple();

        $state = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])
            ->assertOk()->json();

        $this->assertSame('lobby', $state['room']['state']);
        $this->assertSame('Alice', $state['me']['name']);
        $this->assertSame('Bob', $state['partner']['name']);
    }

    public function test_room_is_limited_to_two_players(): void
    {
        $c = $this->createCouple();

        $this->postJson('/api/join', ['name' => 'Intrus', 'code' => $c['code'], 'gender' => 'm'])
            ->assertStatus(400)
            ->assertJson(['error' => 'room_full']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $c = $this->createCouple();

        $this->postJson('/api/state', ['code' => $c['code'], 'token' => 'mauvais-token'])
            ->assertStatus(403);
    }

    public function test_cannot_start_without_partner(): void
    {
        $create = $this->postJson('/api/create', ['name' => 'Alice', 'gender' => 'f'])->json();

        $this->postJson('/api/start', ['code' => $create['code'], 'token' => $create['token']])
            ->assertStatus(400)
            ->assertJson(['error' => 'partner_missing']);
    }

    public function test_full_game_flow_with_scores_and_recap(): void
    {
        $c = $this->createCouple();

        $this->postJson('/api/start', [
            'code' => $c['code'], 'token' => $c['t1'],
            'pack' => 'fun', 'rounds' => 4,
        ])->assertOk()->assertJson(['ai_used' => false]);

        for ($i = 1; $i <= 4; $i++) {
            $state = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
            $round = $state['round'];
            $this->assertSame($i, $round['num']);
            $this->assertSame('answering', $round['status']);
            $this->assertStringNotContainsString('{name}', $round['question']);

            // Les deux répondent ; rien n'est dévoilé avant la seconde réponse.
            $this->postJson('/api/answer', ['code' => $c['code'], 'token' => $c['t1'], 'text' => "A$i"])->assertOk();
            $mid = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t2']])->json();
            $this->assertArrayNotHasKey('target_answer', $mid['round']);
            $this->postJson('/api/answer', ['code' => $c['code'], 'token' => $c['t2'], 'text' => "B$i"])->assertOk();

            $state = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
            $this->assertSame('reveal', $state['round']['status']);

            // Seul le joueur cible peut valider ; il valide "correct".
            $targetToken = $state['round']['i_am_target'] ? $c['t1'] : $c['t2'];
            $otherToken = $state['round']['i_am_target'] ? $c['t2'] : $c['t1'];
            $this->postJson('/api/validate', ['code' => $c['code'], 'token' => $otherToken, 'correct' => true])
                ->assertStatus(403);
            $this->postJson('/api/validate', ['code' => $c['code'], 'token' => $targetToken, 'correct' => true])
                ->assertOk();
        }

        $state = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
        $this->assertSame('finished', $state['room']['state']);
        // 4 bonnes devinettes, cibles alternées : 2 points chacun.
        $this->assertSame(2, $state['me']['score']);
        $this->assertSame(2, $state['partner']['score']);
        $this->assertCount(4, $state['recap']);

        // Replay : retour au salon, scores remis à zéro.
        $this->postJson('/api/replay', ['code' => $c['code'], 'token' => $c['t1']])->assertOk();
        $state = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
        $this->assertSame('lobby', $state['room']['state']);
        $this->assertSame(0, $state['me']['score']);
    }

    public function test_targets_alternate_between_rounds(): void
    {
        $c = $this->createCouple();
        $this->postJson('/api/start', ['code' => $c['code'], 'token' => $c['t1'], 'rounds' => 4]);

        $targets = [];
        for ($i = 1; $i <= 3; $i++) {
            $state = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
            $targets[] = $state['round']['i_am_target'];
            $this->postJson('/api/answer', ['code' => $c['code'], 'token' => $c['t1'], 'text' => 'a']);
            $this->postJson('/api/answer', ['code' => $c['code'], 'token' => $c['t2'], 'text' => 'b']);
            $state = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
            $token = $state['round']['i_am_target'] ? $c['t1'] : $c['t2'];
            $this->postJson('/api/validate', ['code' => $c['code'], 'token' => $token, 'correct' => false]);
        }

        $this->assertNotSame($targets[0], $targets[1]);
        $this->assertNotSame($targets[1], $targets[2]);
    }

    public function test_daily_question_stays_hidden_until_both_answer(): void
    {
        $c = $this->createCouple();

        $this->postJson('/api/daily', ['code' => $c['code'], 'token' => $c['t1'], 'text' => 'Réponse Alice'])
            ->assertOk();

        $bob = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t2']])->json();
        $this->assertTrue($bob['daily']['partner_answered']);
        $this->assertNull($bob['daily']['partner_answer']);

        $this->postJson('/api/daily', ['code' => $c['code'], 'token' => $c['t2'], 'text' => 'Réponse Bob'])
            ->assertOk();

        $alice = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
        $this->assertSame('Réponse Alice', $alice['daily']['my_answer']);
        $this->assertSame('Réponse Bob', $alice['daily']['partner_answer']);

        // Une réponse envoyée ne peut plus être modifiée.
        $this->postJson('/api/daily', ['code' => $c['code'], 'token' => $c['t1'], 'text' => 'Triche'])->assertOk();
        $alice = $this->postJson('/api/state', ['code' => $c['code'], 'token' => $c['t1']])->json();
        $this->assertSame('Réponse Alice', $alice['daily']['my_answer']);
    }
}
