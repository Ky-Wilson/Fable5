<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Génération de questions par IA via OpenRouter (facultatif).
 * En cas d'échec (pas de clé, quota, réseau...), l'appelant retombe
 * sur les packs de questions intégrés.
 */
class OpenRouter
{
    public function enabled(): bool
    {
        return (string) config('services.openrouter.key') !== '';
    }

    /**
     * Demande $n questions de quiz couple dans le style du pack.
     *
     * @return list<string>|null Questions contenant {name}, ou null si échec.
     */
    public function generateQuestions(string $pack, int $n): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $styles = [
            'decouverte' => 'découverte : goûts, souvenirs, habitudes, rêves et personnalité',
            'fun'        => 'humour : questions drôles, absurdes ou décalées',
            'romantique' => 'romantique : souvenirs du couple, tendresse, projets à deux',
            'coquin'     => 'coquin et suggestif, de bon goût : séduction, complicité, charme (sous-entendus, jamais vulgaire)',
            'piment'     => 'pimenté pour un couple adulte : désirs, fantasmes, jeux amoureux — sensuel et direct sans être graphique ni vulgaire',
        ];
        $style = $styles[$pack] ?? $styles['decouverte'];

        $prompt = "Tu écris des questions pour un jeu de couple à distance nommé « Tu me connais ? ». "
            ."Un des deux partenaires répond à une question qui le concerne, l'autre devine sa réponse.\n"
            ."Génère exactement $n questions en français, style $style.\n"
            ."Règles :\n"
            .'- Chaque question parle du partenaire visé en utilisant littéralement le texte {name} à la place de son prénom '
            ."(exemple : \"Quel est le plat préféré de {name} ?\").\n"
            .'- Pour les mots qui s\'accordent en genre, utilise la syntaxe {masculin|féminin} pour la personne visée '
            .'(exemple : "Quel parfum rend {name} {fou|folle} ?") et {p:masculin|féminin} pour son ou sa partenaire.'."\n"
            ."- Une seule phrase par question.\n"
            ."- Questions variées, originales, réponse courte possible.\n"
            .'Réponds UNIQUEMENT avec un tableau JSON de chaînes, sans texte autour.';

        $models = array_merge(
            [config('services.openrouter.model')],
            config('services.openrouter.fallback_models', []),
        );

        foreach ($models as $model) {
            $questions = $this->call($model, $prompt, $n);
            if ($questions !== null) {
                return $questions;
            }
        }

        return null;
    }

    /** @return list<string>|null */
    private function call(string $model, string $prompt, int $n): ?array
    {
        try {
            $response = Http::withToken(config('services.openrouter.key'))
                ->withHeaders([
                    'HTTP-Referer' => config('app.url'),
                    'X-Title'      => 'Tu me connais ?',
                ])
                ->timeout(25)
                ->connectTimeout(10)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model'       => $model,
                    'messages'    => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.9,
                    'max_tokens'  => 1500,
                ]);
        } catch (Throwable $e) {
            Log::warning("OpenRouter injoignable ($model) : {$e->getMessage()}");

            return null;
        }

        if (! $response->successful()) {
            Log::warning("OpenRouter a répondu {$response->status()} ($model)");

            return null;
        }

        $content = (string) $response->json('choices.0.message.content', '');

        // Extrait le premier tableau JSON de la réponse (certains modèles
        // ajoutent du texte ou des balises autour).
        if (! preg_match('/\[.*\]/s', $content, $m)) {
            return null;
        }
        $list = json_decode($m[0], true);
        if (! is_array($list)) {
            return null;
        }

        $questions = [];
        foreach ($list as $q) {
            if (is_string($q) && str_contains($q, '{name}') && mb_strlen($q) <= 300) {
                $questions[] = trim($q);
            }
        }

        // On accepte si au moins la moitié des questions demandées sont valides.
        return count($questions) >= (int) ceil($n / 2)
            ? array_slice($questions, 0, $n)
            : null;
    }
}
