<?php

namespace Tests\Unit;

use App\Support\Questions;
use PHPUnit\Framework\TestCase;

class QuestionsTest extends TestCase
{
    public function test_render_replaces_name(): void
    {
        $this->assertSame(
            'Quel est le plat préféré de Léa ?',
            Questions::render('Quel est le plat préféré de {name} ?', 'Léa', 'f')
        );
    }

    public function test_render_agrees_with_target_gender(): void
    {
        $template = 'Quel parfum rend {name} {fou|folle} ?';

        $this->assertSame('Quel parfum rend Léa folle ?', Questions::render($template, 'Léa', 'f'));
        $this->assertSame('Quel parfum rend Tom fou ?', Questions::render($template, 'Tom', 'm'));
    }

    public function test_render_supports_explicit_target_and_partner_prefixes(): void
    {
        $template = '{name} est {t:fier|fière} de te trouver {p:beau|belle} ?';

        $this->assertSame(
            'Léa est fière de te trouver beau ?',
            Questions::render($template, 'Léa', 'f', 'm')
        );
        $this->assertSame(
            'Tom est fier de te trouver belle ?',
            Questions::render($template, 'Tom', 'm', 'f')
        );
    }

    public function test_every_pack_question_renders_without_leftover_tokens(): void
    {
        foreach (Questions::PACKS as $packId => $pack) {
            foreach ($pack['questions'] as $template) {
                foreach ([['m', 'f'], ['f', 'm']] as [$tg, $pg]) {
                    $rendered = Questions::render($template, 'Camille', $tg, $pg);
                    $this->assertDoesNotMatchRegularExpression(
                        '/[{}|]/',
                        $rendered,
                        "Jeton non résolu dans le pack $packId : $template"
                    );
                }
            }
        }
    }

    public function test_every_daily_question_renders_without_leftover_tokens(): void
    {
        foreach (Questions::DAILY as $template) {
            foreach (['m', 'f'] as $gender) {
                $this->assertDoesNotMatchRegularExpression(
                    '/[{}|]/',
                    Questions::render($template, '', $gender)
                );
            }
        }
    }

    public function test_piment_pack_exists_with_enough_questions(): void
    {
        $this->assertArrayHasKey('piment', Questions::PACKS);
        $this->assertGreaterThanOrEqual(20, count(Questions::PACKS['piment']['questions']));
    }
}
