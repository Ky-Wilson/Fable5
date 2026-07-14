<?php

namespace App\Support;

use App\Models\Room;
use Carbon\CarbonImmutable;

/**
 * Banque de questions intégrée.
 *
 * Syntaxe des modèles :
 * - {name}            → prénom du joueur visé (celui dont on devine la réponse)
 * - {fou|folle}       → accord selon le sexe du joueur visé (masculin|féminin)
 * - {t:fier|fière}    → idem, forme explicite
 * - {p:beau|belle}    → accord selon le sexe du/de la PARTENAIRE (celui qui devine)
 */
class Questions
{
    public const PACKS = [
        'decouverte' => [
            'label' => 'Découverte',
            'icon' => 'search',
            'desc'  => 'Apprendre à se connaître (encore mieux)',
            'questions' => [
                'Quel est le plat préféré de {name} ?',
                'Quelle est la plus grande peur de {name} ?',
                'Quel pays {name} rêve de visiter ?',
                'Quel est le film préféré de {name} ?',
                'Quelle habitude {name} aimerait perdre ?',
                'Quel était le métier de rêve de {name} enfant ?',
                'Quelle chanson {name} écoute en boucle en ce moment ?',
                "Qu'est-ce qui met {name} de mauvaise humeur à coup sûr ?",
                'Quel super-pouvoir {name} choisirait ?',
                "Quel est le souvenir d'enfance préféré de {name} ?",
                'De quoi {name} est le plus {fier|fière} dans sa vie ?',
                'Si {name} gagnait au loto, quel serait son premier achat ?',
                'Quelle est la saison préférée de {name} ?',
                'Quel dessert fait complètement craquer {name} ?',
                'Quelle célébrité {name} inviterait à dîner ?',
                'Quel est le pire métier imaginable pour {name} ?',
                'Que fait {name} en premier le matin ?',
                'Quelle appli {name} utilise le plus sur son téléphone ?',
                'Thé, café ou chocolat chaud pour {name} ?',
                'Quel animal représenterait le mieux {name} ?',
                'Dans quelle matière {name} était {bon|bonne} à l\'école ?',
                'Quel bruit ou tic agace {name} plus que tout ?',
                "Plutôt soirée canapé ou sortie improvisée pour {name} ?",
                'Quel talent {name} aimerait apprendre du jour au lendemain ?',
            ],
        ],
        'fun' => [
            'label' => 'Fun',
            'icon' => 'smile',
            'desc'  => 'Pour rigoler un bon coup',
            'questions' => [
                'Quel emoji {name} utilise le plus ?',
                'Quelle chanson {name} chante sous la douche ?',
                'Quel est le talent caché (ou complètement inutile) de {name} ?',
                'Si {name} était un personnage de dessin animé, lequel ?',
                'Quelle est la pire mode que {name} a suivie ?',
                'Quel plat {name} rate à chaque fois ?',
                "Combien de temps {name} survivrait dans un film d'horreur ?",
                'Quelle phrase {name} répète tout le temps ?',
                'Si {name} devait manger un seul plat toute sa vie, lequel ?',
                'Quelle bêtise {name} faisait enfant ?',
                'Si {name} était un objet de la maison, lequel ?',
                'Quel serait le nom de scène de {name} en star de la chanson ?',
                'Quelle série {name} pourrait regarder en entier pour la troisième fois ?',
                "Que ferait {name} en premier en cas d'apocalypse zombie ?",
                'Quel est le pire cadeau que {name} a déjà reçu ?',
                'Dans quel domaine {name} se croit très {fort|forte}... alors que pas du tout ?',
                'Quel âge mental a réellement {name} ?',
                "Si {name} pouvait échanger sa vie avec quelqu'un pour une journée, avec qui ?",
                'Quel est le pire réflexe de {name} quand son téléphone sonne trop tôt ?',
                'Quelle victoire complètement inutile rendrait {name} très {fier|fière} ?',
                '{name} est {perdu|perdue} sur une île déserte : quel objet inutile emporte-t-{il|elle} ?',
                'Quelle danse {name} sort quand {il|elle} croit que personne ne regarde ?',
                'Si la vie de {name} était une télé-réalité, quel en serait le titre ?',
                'Quel mensonge {name} sort le plus souvent (« j\'arrive dans 5 minutes »...) ?',
            ],
        ],
        'romantique' => [
            'label' => 'Romantique',
            'icon' => 'heart',
            'desc'  => 'Souvenirs et petits mots doux',
            'questions' => [
                'Quel a été le moment préféré de {name} dans votre histoire ?',
                'Quelle chanson fait penser {name} à vous deux ?',
                'Quelle a été la première impression de {name} en te rencontrant ?',
                'Quel geste tendre {name} préfère recevoir ?',
                'De quelle destination de voyage en amoureux rêve {name} ?',
                "Quel surnom {name} préfère qu'on lui donne ?",
                'Quel moment de votre relation {name} aimerait revivre ?',
                "Qu'est-ce qui fait le plus craquer {name} chez toi ?",
                "Quelle est la déclaration d'amour idéale pour {name} ?",
                'Quel petit détail de toi {name} adore ?',
                "Pour {name}, c'est quoi la soirée en amoureux parfaite ?",
                'Quel projet à deux fait le plus rêver {name} ?',
                'Quelle photo de vous deux {name} préfère ?',
                "Qu'est-ce que {name} admire le plus chez toi ?",
                'Quel est le plus beau cadeau que {name} ait reçu de toi ?',
                'Comment {name} imagine votre vie dans dix ans ?',
                'Quelle attention toute simple rend {name} {heureux|heureuse} à coup sûr ?',
                'Quel est le mot doux préféré de {name} ?',
                "Dans quel lieu {name} rêverait de t'embrasser ?",
                "Qu'est-ce qui manque le plus à {name} quand vous êtes loin l'un de l'autre ?",
                'À quel moment {name} s\'est {dit|dite} pour la première fois : « c\'est {p:lui|elle} » ?',
                'Quelle chose {name} aimerait te dire plus souvent ?',
                'Quel rituel à deux {name} chérit le plus ?',
                "Qu'est-ce que {name} prépare secrètement pour te faire plaisir ?",
            ],
        ],
        'coquin' => [
            'label' => 'Coquin',
            'icon' => 'flame',
            'desc'  => 'Séduction, charme et sous-entendus',
            'questions' => [
                'Quelle tenue te rend irrésistible aux yeux de {name} ?',
                "Quel compliment fait le plus d'effet à {name} ?",
                'Où {name} rêverait de passer une nuit avec toi ?',
                'Quel geste fait totalement craquer {name} ?',
                'Quelle partie de ton corps {name} préfère ?',
                "Quel serait le programme d'une soirée (très) rapprochée selon {name} ?",
                "Quel est le message le plus osé que {name} t'a déjà envoyé ?",
                'Quel parfum ou quelle odeur rend {name} {fou|folle} ?',
                "Slow sensuel ou regard qui en dit long : quelle est l'arme de séduction de {name} ?",
                'Quel souvenir à deux fait encore rougir {name} ?',
                'Quelle est la plus grande audace de {name} ?',
                'Massage ou câlin interminable : que préfère {name} ?',
                'Quel surnom secret {name} aimerait te donner ?',
                'Quel rendez-vous surprise ferait complètement chavirer {name} ?',
                "Lumière tamisée, bougies ou pénombre totale : l'ambiance préférée de {name} ?",
                "Quelle petite habitude à toi trouble {name} plus qu'{il|elle} ne l'avoue ?",
                'Quel est le fantasme de voyage en amoureux de {name} ?',
                'À quel moment {name} te trouve le plus {p:séduisant|séduisante} ?',
                'Quelle chanson mettrait {name} pour une soirée en tête-à-tête ?',
                'Quel est le baiser préféré de {name} ?',
                'Quel regard de toi {name} ne sait pas ignorer ?',
                'Quelle danse {name} rêverait de partager avec toi, collés-serrés ?',
                "Qu'est-ce que {name} remarque en premier quand tu t'habilles pour sortir ?",
                'Petit-déjeuner au lit ou grasse matinée câline : que choisit {name} ?',
            ],
        ],
        'piment' => [
            'label' => 'Piment',
            'icon' => 'pepper',
            'desc'  => 'Pour pimenter votre vie de couple (réservé aux adultes consentants)',
            'questions' => [
                'Quel fantasme {name} aimerait réaliser avec toi ?',
                'Quelle partie de ton corps {name} pourrait embrasser pendant des heures ?',
                'Quel est le moment le plus torride que {name} ait vécu avec toi ?',
                'Quelle nouveauté {name} aimerait tester au lit ?',
                'Quel endroit (hors de la chambre) fait fantasmer {name} pour un moment coquin ?',
                'Douche à deux ou massage aux huiles : que choisirait {name} ?',
                'Quel message brûlant {name} rêverait de recevoir de toi en pleine journée ?',
                'Quelle tenue {name} rêve secrètement de te voir porter... ou enlever ?',
                'Yeux bandés ou lumières allumées : que préfère {name} ?',
                "Combien de temps {name} tiendrait au jeu « interdit de se toucher » ?",
                "Quel mot doux (ou pas si doux) {name} adore entendre dans l'intimité ?",
                'Sur une échelle de 1 à 10, à quel point {name} est {joueur|joueuse} sous la couette ?',
                'Quel souvenir pimenté {name} repasse en boucle dans sa tête ?',
                'Matin câlin ou nuit blanche : le moment préféré de {name} ?',
                'Quel accessoire {name} oserait ajouter à vos jeux ?',
                'Si vous jouiez un jeu de rôle coquin, quel duo choisirait {name} ?',
                "Qu'est-ce que {name} n'a jamais osé te demander ?",
                'Quelle promesse pimentée {name} veut te faire pour vos prochaines retrouvailles ?',
                'Quel serait le programme du week-end 100 % piment rêvé de {name} ?',
                'Préliminaires interminables ou passion express : la préférence de {name} ?',
                'Quel endroit de ton corps {name} trouve le plus envoûtant ?',
                "Quelle chanson {name} mettrait pour une soirée qui monte en température ?",
                'Qui de vous deux craque en premier après un regard qui en dit long, selon {name} ?',
                'Quel gage coquin {name} aimerait te donner... et lequel aimerait-{il|elle} recevoir ?',
            ],
        ],
    ];

    // Questions du jour : chacun répond pour soi, les réponses se dévoilent
    // quand les deux ont répondu. Les accords {a|b} suivent le sexe du lecteur.
    public const DAILY = [
        'Quel moment de ta journée aurais-tu aimé partager avec moi ?',
        "Qu'est-ce qui t'a fait sourire aujourd'hui ?",
        'Si on était ensemble ce soir, on ferait quoi ?',
        'Quelle est la petite victoire de ta journée ?',
        "Qu'est-ce qui t'a le plus manqué chez moi aujourd'hui ?",
        "Raconte un détail de ta journée que tu ne m'aurais pas dit sinon.",
        'Quelle musique a accompagné ta journée ?',
        'De quoi as-tu besoin ce soir : réconfort, rires ou silence à deux ?',
        "Quel plat aurais-tu voulu qu'on cuisine ensemble ce soir ?",
        'Si tu pouvais te téléporter près de moi 10 minutes, ce serait pour faire quoi ?',
        "Quelle pensée pour nous deux as-tu eue aujourd'hui ?",
        "Qu'attends-tu le plus de nos prochaines retrouvailles ?",
        'Quel a été le moment le plus difficile de ta journée ?',
        'Décris le câlin dont tu aurais besoin là, tout de suite.',
        "Quelle chose as-tu apprise aujourd'hui ?",
        "Sur une échelle de 1 à 10, ton niveau d'énergie aujourd'hui... et pourquoi ?",
        'Quel rêve (la nuit) as-tu fait récemment ?',
        "Que ferais-tu si j'apparaissais à ta porte maintenant ?",
        "Quelle habitude à deux voudrais-tu qu'on crée ?",
        'Cite trois choses pour lesquelles tu es {reconnaissant|reconnaissante} aujourd\'hui.',
        "Quel souvenir de nous t'a traversé l'esprit récemment ?",
        "Qu'aimerais-tu qu'on planifie ensemble ?",
        'Si ta journée était un titre de film, ce serait quoi ?',
        'Quelle est ta météo intérieure aujourd\'hui ?',
        "Qu'est-ce que j'ai fait récemment qui t'a fait plaisir ?",
        'Quelle question aimerais-tu que je te pose plus souvent ?',
        "Qu'est-ce qui te préoccupe en ce moment ?",
        "Quel petit plaisir t'es-tu accordé aujourd'hui ?",
        'Décris ta journée en trois mots.',
        'Quelle promesse veux-tu me faire pour demain ?',
    ];

    /**
     * Rend un modèle de question : prénom + accords en genre.
     *
     * @param string $targetGender  'm' ou 'f' — joueur visé ({a|b} et {t:a|b})
     * @param string $partnerGender 'm' ou 'f' — partenaire ({p:a|b})
     */
    public static function render(string $template, string $targetName, string $targetGender, string $partnerGender = 'm'): string
    {
        $out = str_replace('{name}', $targetName, $template);

        return preg_replace_callback(
            '/\{(?:([tp]):)?([^{}|]*)\|([^{}|]*)\}/u',
            function (array $m) use ($targetGender, $partnerGender) {
                $gender = ($m[1] === 'p') ? $partnerGender : $targetGender;

                return $gender === 'f' ? $m[3] : $m[2];
            },
            $out
        );
    }

    /** Tire $n questions au hasard dans un pack (sans doublon). */
    public static function pick(string $pack, int $n): array
    {
        $pool = self::PACKS[$pack]['questions'] ?? self::PACKS['decouverte']['questions'];
        shuffle($pool);

        return array_slice($pool, 0, min($n, count($pool)));
    }

    /**
     * Question du jour déterministe : avance d'une question par jour
     * depuis la création du salon, sans répétition avant épuisement.
     */
    public static function dailyFor(Room $room, string $day): string
    {
        $start = CarbonImmutable::parse($room->created_at)->startOfDay();
        $index = max(0, $start->diffInDays(CarbonImmutable::parse($day)->startOfDay()));

        return self::DAILY[(int) $index % count(self::DAILY)];
    }

    /** Liste des packs pour l'interface. */
    public static function packList(): array
    {
        return collect(self::PACKS)->map(fn ($pack, $id) => [
            'id'    => $id,
            'label' => $pack['label'],
            'icon'  => $pack['icon'],
            'desc'  => $pack['desc'],
        ])->values()->all();
    }
}
